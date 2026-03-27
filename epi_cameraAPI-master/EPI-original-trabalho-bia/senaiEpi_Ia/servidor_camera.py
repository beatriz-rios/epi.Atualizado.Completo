import cv2
import mysql.connector
import numpy as np
from ultralytics import YOLO
import threading
import time
import os
import winsound
from flask import Flask, Response, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# ==============================================================================
# 1. CONFIGURAÇÕES GERAIS E BANCO DE DADOS
# ==============================================================================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',       # Confirme sua senha
    'database': 'epi_guard',
    'port': 3306
}

EPI_OCULOS_ID = 1
EPI_CAPACETE_ID = 2
EPI_CORPO_ID = 3 
EPI_LUVAS_ID = 4 # NOVO: ID do EPI Luva

# Classes integradas mantendo a ordem para não quebrar seus IDs
CLASSES_YOLO = [
    "hard hat", "helmet", "safety helmet",                          # 0, 1, 2
    "person",                                                       # 3
    "glasses", "sunglasses", "reading glasses",                     # 4, 5, 6
    "safety goggles", "protective eyewear", "safety glasses",       # 7, 8, 9
    "welding jacket", "leather jacket", "protective jacket",        # 10, 11, 12
    "welding apron", "leather apron", "apron",                      # 13, 14, 15
    "glove", "gloves"                                               # 16, 17 (NOVO)
]

BLUSAO_CLASSES = [10, 11, 12]
AVENTAL_CLASSES = [13, 14, 15]
GLOVE_CLASSES = [16, 17] # NOVO: IDs das luvas
HELMET_CLASSES = [0, 1, 2]
PERSON_CLASS = 3
ALL_EYEWEAR = [4, 5, 6, 7, 8, 9]
LIMITE_CONFIANCA_FACE = 60

# ==============================================================================
# VARIÁVEIS GLOBAIS
# ==============================================================================
camera_ativa = False
nomes_conhecidos = {}
modelo_treinado = False
tempo_infracao = {}

frame_atual = None
lock_frame = threading.Lock()

# Variáveis de desenho
ultimo_desenho_capacetes = []
ultimo_desenho_oculos = []
ultimo_desenho_oculos_vermelho = []
ultimo_desenho_blusoes = [] 
ultimo_desenho_aventais = [] 
ultimo_desenho_luvas = [] # NOVO

foco_nome = "Desconhecido"
foco_status = "ANALISANDO..."
foco_cor = (255, 255, 0)
foco_bbox = None

# ==============================================================================
# 2. INICIALIZAÇÃO DOS MODELOS (YOLO E FACIAL)
# ==============================================================================
print("[SISTEMA] Carregando Modelos YOLO e HaarCascades...")
model = YOLO("yolov8s-worldv2.pt")
model.set_classes(CLASSES_YOLO)

face_cascade = cv2.CascadeClassifier(
    cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
recognizer = cv2.face.LBPHFaceRecognizer_create()

# ==============================================================================
# 3. FUNÇÕES DE SUPORTE E BANCO DE DADOS
# ==============================================================================

def inicializar_banco():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("CREATE TABLE IF NOT EXISTS alunos (id INT PRIMARY KEY, nome VARCHAR(100))")
        cursor.execute("CREATE TABLE IF NOT EXISTS amostras_facial (id INT AUTO_INCREMENT PRIMARY KEY, aluno_id INT, imagem LONGBLOB)")
        cursor.execute("CREATE TABLE IF NOT EXISTS ocorrencias (id INT AUTO_INCREMENT PRIMARY KEY, aluno_id INT, data_hora DATETIME, epi_id INT)")
        cursor.execute("CREATE TABLE IF NOT EXISTS evidencias (id INT AUTO_INCREMENT PRIMARY KEY, ocorrencia_id INT, imagem LONGBLOB)")
        cursor.execute("CREATE TABLE IF NOT EXISTS epis (id INT PRIMARY KEY, nome VARCHAR(50))")
        
        # NOVO: Adicionado o ID 4 para Luvas
        cursor.execute("INSERT IGNORE INTO epis (id, nome) VALUES (1, 'Oculos'), (2, 'Capacete'), (3, 'Avental/Blusao'), (4, 'Luvas')")
        conn.commit()
        conn.close()
        print("[BD] Banco inicializado com sucesso.")
    except Exception as e:
        print(f"[ERRO BD] {e}")


def treinar_modelo():
    global modelo_treinado, nomes_conhecidos
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("SELECT id, nome FROM alunos")
        nomes_conhecidos = {row[0]: row[1] for row in cursor.fetchall()}

        cursor.execute("SELECT aluno_id, imagem FROM amostras_facial")
        faces, ids = [], []
        for uid, blob in cursor.fetchall():
            if blob:
                nparr = np.frombuffer(blob, np.uint8)
                img = cv2.imdecode(nparr, cv2.IMREAD_GRAYSCALE)
                if img is not None:
                    faces.append(cv2.resize(img, (200, 200)))
                    ids.append(uid)

        if len(faces) > 0:
            recognizer.train(faces, np.array(ids))
            modelo_treinado = True
            print(f"[TREINO] Modelo facial treinado com {len(faces)} faces.")
        else:
            modelo_treinado = False
            print("[TREINO] Nenhuma face cadastrada.")
        conn.close()
    except Exception as e:
        print(f"[ERRO TREINO] {e}")


# NOVO: Adicionado o parametro falta_luvas
def registrar_multa(frame_evidencia, aluno_id, falta_capacete, falta_oculos, falta_corpo, falta_luvas):
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        _, buffer = cv2.imencode('.jpg', frame_evidencia)
        imagem_bytes = buffer.tobytes()

        if falta_capacete:
            cursor.execute("INSERT INTO ocorrencias (aluno_id, data_hora, epi_id) VALUES (%s, NOW(), %s)", (aluno_id, EPI_CAPACETE_ID))
            id_last = cursor.lastrowid
            cursor.execute("INSERT INTO evidencias (ocorrencia_id, imagem) VALUES (%s, %s)", (id_last, imagem_bytes))

        if falta_oculos:
            cursor.execute("INSERT INTO ocorrencias (aluno_id, data_hora, epi_id) VALUES (%s, NOW(), %s)", (aluno_id, EPI_OCULOS_ID))
            id_last = cursor.lastrowid
            cursor.execute("INSERT INTO evidencias (ocorrencia_id, imagem) VALUES (%s, %s)", (id_last, imagem_bytes))

        if falta_corpo:
            cursor.execute("INSERT INTO ocorrencias (aluno_id, data_hora, epi_id) VALUES (%s, NOW(), %s)", (aluno_id, EPI_CORPO_ID))
            id_last = cursor.lastrowid
            cursor.execute("INSERT INTO evidencias (ocorrencia_id, imagem) VALUES (%s, %s)", (id_last, imagem_bytes))

        # NOVO: Registro da Luva
        if falta_luvas:
            cursor.execute("INSERT INTO ocorrencias (aluno_id, data_hora, epi_id) VALUES (%s, NOW(), %s)", (aluno_id, EPI_LUVAS_ID))
            id_last = cursor.lastrowid
            cursor.execute("INSERT INTO evidencias (ocorrencia_id, imagem) VALUES (%s, %s)", (id_last, imagem_bytes))

        conn.commit()
        conn.close()
        print(f"[MULTA] Registrada no banco para o ID {aluno_id}.")
        threading.Thread(target=lambda: winsound.Beep(2500, 1000)).start()
    except Exception as e:
        print(f"[ERRO MULTA] {e}")


def verificar_hsv_capacete(img_crop):
    if img_crop is None or img_crop.size == 0:
        return False
    h, w = img_crop.shape[:2]
    topo = img_crop[0:int(h*0.7), :]
    hsv = cv2.cvtColor(topo, cv2.COLOR_BGR2HSV)
    mask_valid = cv2.inRange(hsv, np.array(
        [0, 0, 50]), np.array([180, 255, 255]))
    ratio = cv2.countNonZero(mask_valid) / (topo.shape[0]*topo.shape[1])
    return ratio > 0.35


def verificar_cor_epi_oculos(img_crop):
    if img_crop is None or img_crop.size == 0:
        return False
    img_crop = cv2.resize(img_crop, (220, 100))
    img_crop = cv2.GaussianBlur(img_crop, (3, 3), 0)
    hsv = cv2.cvtColor(img_crop, cv2.COLOR_BGR2HSV)

    lower_yellow = np.array([15, 100, 100])
    upper_yellow = np.array([38, 255, 255])
    mask_yellow = cv2.inRange(hsv, lower_yellow, upper_yellow)

    lower_red1 = np.array([0, 130, 70])
    upper_red1 = np.array([10, 255, 255])
    lower_red2 = np.array([160, 130, 70])
    upper_red2 = np.array([180, 255, 255])
    mask_red = cv2.inRange(hsv, lower_red1, upper_red1) + \
        cv2.inRange(hsv, lower_red2, upper_red2)

    kernel = np.ones((3, 3), np.uint8)
    mask_yellow = cv2.morphologyEx(mask_yellow, cv2.MORPH_OPEN, kernel)
    mask_red = cv2.morphologyEx(mask_red, cv2.MORPH_OPEN, kernel)

    total_amarelo = cv2.countNonZero(mask_yellow)
    total_vermelho = cv2.countNonZero(mask_red)

    area_total = img_crop.shape[0] * img_crop.shape[1]
    percentual_amarelo = (total_amarelo / area_total) * 100
    percentual_vermelho = (total_vermelho / area_total) * 100

    if percentual_amarelo > 0.8 or percentual_vermelho > 0.5:
        return True
    return False

# ==============================================================================
# 4. THREADS DE VÍDEO E IA
# ==============================================================================

def capturar_frames():
    global frame_atual, camera_ativa
    cap = None
    while True:
        if camera_ativa:
            if cap is None:
                cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
                cap.set(3, 1280)
                cap.set(4, 720)
                if not cap.isOpened():
                    print("[ERRO] Nao foi possivel abrir a camera.")
                    cap = None
                    camera_ativa = False
                    continue
                print("[SISTEMA] Webcam aberta pelo Servidor EPI.")

            ret, frame = cap.read()
            if ret:
                with lock_frame:
                    frame_atual = frame.copy()
            else:
                time.sleep(0.01)
        else:
            if cap is not None:
                cap.release()
                cap = None
                with lock_frame:
                    frame_atual = None
                print("[SISTEMA] Webcam liberada pelo Servidor EPI.")
            time.sleep(0.5)


def processar_ia():
    global frame_atual, camera_ativa
    global ultimo_desenho_capacetes, ultimo_desenho_oculos, ultimo_desenho_oculos_vermelho
    global ultimo_desenho_blusoes, ultimo_desenho_aventais, ultimo_desenho_luvas # NOVO: luvas
    global foco_nome, foco_status, foco_cor, foco_bbox
    global tempo_infracao, modelo_treinado, nomes_conhecidos

    frame_count = 0

    while True:
        if not camera_ativa:
            tempo_infracao.clear()
            foco_bbox = None
            time.sleep(0.5)
            continue

        if frame_atual is None:
            time.sleep(0.01)
            continue

        with lock_frame:
            frame = frame_atual.copy()

        frame_count += 1
        if frame_count % 3 != 0:
            continue

        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        results = model.predict(frame, conf=0.20, verbose=False, imgsz=800)

        pessoas_yolo, capacetes, oculos_detectados = [], [], []
        blusoes_detectados, aventais_detectados = [], [] 
        luvas_detectadas_raw = [] # NOVO: Para processar a lógica espacial

        for r in results:
            for box in r.boxes:
                coords = list(map(int, box.xyxy[0]))
                cls = int(box.cls[0])
                if cls == PERSON_CLASS:
                    pessoas_yolo.append(coords)
                elif cls in HELMET_CLASSES:
                    capacetes.append(coords)
                elif cls in ALL_EYEWEAR:
                    oculos_detectados.append(coords)
                elif cls in BLUSAO_CLASSES: 
                    blusoes_detectados.append(coords)
                elif cls in AVENTAL_CLASSES: 
                    aventais_detectados.append(coords)
                elif cls in GLOVE_CLASSES: # NOVO: Identifica luvas
                    luvas_detectadas_raw.append(coords)

        pessoa_foco = None
        maior_area = 0
        for p in pessoas_yolo:
            area = (p[2]-p[0]) * (p[3]-p[1])
            if area > maior_area:
                maior_area = area
                pessoa_foco = p

        temp_capacetes = []
        temp_oculos = []
        temp_oculos_vermelho = []
        temp_blusoes = [] 
        temp_aventais = [] 
        luvas_filtradas = [] # NOVO: Lista final de luvas validadas
        
        temp_foco_bbox = None
        temp_foco_nome = "Desconhecido"
        temp_foco_status = "ANALISANDO..."
        temp_foco_cor = (255, 255, 0)

        if pessoa_foco is not None:
            temp_foco_bbox = pessoa_foco
            px1, py1, px2, py2 = pessoa_foco
            h_img, w_img = frame.shape[:2]
            px1, py1 = max(0, px1), max(0, py1)
            px2, py2 = min(w_img, px2), min(h_img, py2)

            # Reconhecimento Facial
            roi_gray = gray[py1:py2, px1:px2]
            faces_haar = face_cascade.detectMultiScale(roi_gray, 1.1, 5)
            identidade_id = None

            if len(faces_haar) > 0 and modelo_treinado:
                (fx, fy, fw, fh) = max(faces_haar, key=lambda b: b[2]*b[3])
                try:
                    fh_corte = int(fh * 0.60)
                    roi_face = cv2.resize(
                        roi_gray[fy:fy+fh_corte, fx:fx+fw], (200, 200))
                    uid, dist = recognizer.predict(roi_face)
                    if dist < LIMITE_CONFIANCA_FACE:
                        identidade_id = uid
                        temp_foco_nome = nomes_conhecidos.get(uid, f"ID {uid}")
                except:
                    pass

            h_person = py2 - py1
            zona_cabeca = py1 + (h_person * 0.35)
            zona_olhos = py1 + (h_person * 0.55)

            tem_capacete, tem_oculos = False, False
            tem_blusao, tem_avental = False, False 

            # Checagem Capacete
            for (hx1, hy1, hx2, hy2) in capacetes:
                hcx = (hx1 + hx2) / 2
                if px1 < hcx < px2 and hy1 < zona_cabeca:
                    if verificar_hsv_capacete(frame[hy1:hy2, hx1:hx2]):
                        tem_capacete = True
                        temp_capacetes.append((hx1, hy1, hx2, hy2))

            # Checagem Óculos
            for (ox1, oy1, ox2, oy2) in oculos_detectados:
                ocx, ocy = (ox1 + ox2) / 2, (oy1 + oy2) / 2
                if px1 < ocx < px2 and py1 < ocy < zona_olhos:
                    largura = ox2 - ox1
                    margem = int(largura * 0.5)
                    crop_x1 = max(0, ox1 - margem)
                    crop_x2 = min(w_img, ox2 + margem)
                    crop_oculos = frame[oy1:oy2, crop_x1:crop_x2]

                    if verificar_cor_epi_oculos(crop_oculos):
                        tem_oculos = True
                        temp_oculos.append((ox1, oy1, ox2, oy2))
                    else:
                        temp_oculos_vermelho.append((ox1, oy1, ox2, oy2))
                        
            # Checagem do Blusão
            for (bx1, by1, bx2, by2) in blusoes_detectados:
                centro_x = (bx1 + bx2) // 2
                centro_y = (by1 + by2) // 2
                if px1 < centro_x < px2 and py1 < centro_y < py2:
                    tem_blusao = True
                    temp_blusoes.append((bx1, by1, bx2, by2))
                    
            # Checagem do Avental
            for (ax1, ay1, ax2, ay2) in aventais_detectados:
                centro_x = (ax1 + ax2) // 2
                centro_y = (ay1 + ay2) // 2
                if px1 < centro_x < px2 and py1 < centro_y < py2:
                    tem_avental = True
                    temp_aventais.append((ax1, ay1, ax2, ay2))

            # NOVO: LÓGICA DE VALIDAÇÃO ESPACIAL DAS LUVAS
            for coords in luvas_detectadas_raw:
                lx1, ly1, lx2, ly2 = coords
                lcx, lcy = (lx1 + lx2) / 2, (ly1 + ly2) / 2
                
                # Verificação 1: Perto da pessoa (Margem de 20%)
                margem_h = (px2 - px1) * 0.20
                if (px1 - margem_h) < lcx < (px2 + margem_h) and (py1 - 50) < lcy < py2:
                    
                    # Verificação 2: Filtro de proximidade (NMS Manual)
                    falso_positivo = False
                    dist_minima = (px2 - px1) * 0.15
                    
                    for (fx1, fy1, fx2, fy2) in luvas_filtradas:
                        fcx, fcy = (fx1 + fx2) / 2, (fy1 + fy2) / 2
                        dist = ((lcx - fcx)**2 + (lcy - fcy)**2)**0.5
                        if dist < dist_minima:
                            falso_positivo = True
                            break
                    
                    if not falso_positivo:
                        luvas_filtradas.append((lx1, ly1, lx2, ly2))

            # Validação Final de EPIs
            tem_luvas = (len(luvas_filtradas) >= 2) # Exige o par
            tem_corpo = tem_blusao or tem_avental
            
            falha = not (tem_capacete and tem_oculos and tem_corpo and tem_luvas)
            
            temp_foco_cor = (0, 255, 0)
            temp_foco_status = "APROVADO"

            if falha:
                temp_foco_cor = (0, 0, 255)
                temp_foco_status = "INFRACAO"
                if not tem_capacete:
                    temp_foco_status += " [CAPACETE]"
                if not tem_oculos:
                    temp_foco_status += " [OCULOS]"
                if not tem_corpo:
                    temp_foco_status += " [BLUSAO/AVENTAL]"
                if not tem_luvas: # NOVO
                    temp_foco_status += " [LUVAS]"

                if identidade_id:
                    agora = time.time()
                    if identidade_id not in tempo_infracao:
                        tempo_infracao[identidade_id] = agora
                    elif agora - tempo_infracao[identidade_id] > 3.0:
                        # Chama registro de multa com todos os parâmetros
                        threading.Thread(target=registrar_multa, args=(
                            frame.copy(), identidade_id, not tem_capacete, not tem_oculos, not tem_corpo, not tem_luvas)).start()
                        tempo_infracao[identidade_id] = agora + 10
            else:
                if identidade_id in tempo_infracao:
                    del tempo_infracao[identidade_id]

        foco_bbox = temp_foco_bbox
        foco_nome = temp_foco_nome
        foco_status = temp_foco_status
        foco_cor = temp_foco_cor
        
        ultimo_desenho_capacetes = temp_capacetes
        ultimo_desenho_oculos = temp_oculos
        ultimo_desenho_oculos_vermelho = temp_oculos_vermelho
        ultimo_desenho_blusoes = temp_blusoes 
        ultimo_desenho_aventais = temp_aventais 
        ultimo_desenho_luvas = luvas_filtradas # NOVO
        
        time.sleep(0.01)


def gerar_frames():
    global frame_atual

    while True:
        if frame_atual is None:
            time.sleep(0.01)
            continue

        with lock_frame:
            frame_display = frame_atual.copy()

        for (hx1, hy1, hx2, hy2) in ultimo_desenho_capacetes:
            cv2.rectangle(frame_display, (hx1, hy1),
                          (hx2, hy2), (0, 255, 0), 2)

        for (ox1, oy1, ox2, oy2) in ultimo_desenho_oculos:
            cv2.rectangle(frame_display, (ox1, oy1),
                          (ox2, oy2), (0, 255, 0), 2)
            cv2.putText(frame_display, "EPI OK", (ox1, oy1-5),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

        for (ox1, oy1, ox2, oy2) in ultimo_desenho_oculos_vermelho:
            cv2.rectangle(frame_display, (ox1, oy1),
                          (ox2, oy2), (0, 0, 255), 2)
            cv2.putText(frame_display, "COMUM", (ox1, oy2+15),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 255), 2)
                    
        # Desenho do Blusão
        for (bx1, by1, bx2, by2) in ultimo_desenho_blusoes:
            cv2.rectangle(frame_display, (bx1, by1), (bx2, by2), (0, 255, 0), 2)
            cv2.putText(frame_display, "BLUSAO", (bx1, by1 - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

        # Desenho do Avental
        for (ax1, ay1, ax2, ay2) in ultimo_desenho_aventais:
            cv2.rectangle(frame_display, (ax1, ay1), (ax2, ay2), (0, 255, 0), 2)
            cv2.putText(frame_display, "AVENTAL", (ax1, ay1 - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

        # NOVO: Desenho das Luvas
        for (lx1, ly1, lx2, ly2) in ultimo_desenho_luvas:
            cv2.rectangle(frame_display, (lx1, ly1), (lx2, ly2), (0, 255, 0), 2)
            cv2.putText(frame_display, "LUVA", (lx1, ly1 - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

        if foco_bbox is not None:
            px1, py1, px2, py2 = foco_bbox
            cv2.rectangle(frame_display, (px1, py1), (px2, py2), foco_cor, 2)
            cv2.putText(frame_display, f"{foco_nome} | {foco_status}",
                        (px1, py1-10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, foco_cor, 2)

        ret, buffer = cv2.imencode('.jpg', frame_display)
        if not ret:
            continue

        yield (b'--frame\r\n' b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
        time.sleep(0.03)

# ==============================================================================
# 5. ROTAS DA API FLASK
# ==============================================================================

@app.route('/status_ia')
def status_ia():
    global foco_nome, foco_status
    return jsonify({"nome": foco_nome, "status": foco_status})


@app.route('/video_feed')
def video_feed():
    return Response(gerar_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')


@app.route('/ligar')
def ligar_camera():
    global camera_ativa, tempo_infracao
    camera_ativa = True
    tempo_infracao.clear()
    return {"status": "Ligado"}


@app.route('/desligar')
def desligar_camera():
    global camera_ativa, frame_atual, tempo_infracao
    camera_ativa = False
    with lock_frame:
        frame_atual = None
    tempo_infracao.clear()
    return {"status": "Desligado"}


if __name__ == '__main__':
    inicializar_banco()
    treinar_modelo()

    threading.Thread(target=capturar_frames, daemon=True).start()
    threading.Thread(target=processar_ia, daemon=True).start()

    print("-----------------------------------------")
    print("SERVIDOR EPI GUARD + RECONHECIMENTO FACIAL")
    print("RODANDO EM HTTP://LOCALHOST:5000")
    print("-----------------------------------------")
    app.run(host='0.0.0.0', port=5000, debug=False, threaded=True)