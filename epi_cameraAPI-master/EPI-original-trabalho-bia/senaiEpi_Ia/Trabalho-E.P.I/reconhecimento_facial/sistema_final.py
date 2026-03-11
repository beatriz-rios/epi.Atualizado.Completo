import cv2
import mysql.connector
import numpy as np
import threading
import time
from flask import Flask, Response, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# ==============================================================================
# 1. CONFIGURAÇÕES DO BANCO DE DADOS
# ==============================================================================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'epi_guard',
    'port': 3308
}

# ==============================================================================
# VARIÁVEIS GLOBAIS (Otimizadas para Performance)
# ==============================================================================
camera_ativa = False
frame_raw = None        # Captura pura da câmera
frame_processado = None  # Resultado com IA e Efeitos
lock_frame = threading.Lock()

# Variáveis de Estado da IA
foco_nome = "Buscando rosto..."
foco_cor = (0, 0, 255)
modelo_treinado = False
nomes_conhecidos = {}

# Variáveis de Cadastro
modo_cadastro = False
cadastro_count = 0
cad_id = 0
cad_nome = ""

LIMITE_CONFIANCA_FACE = 60

# ==============================================================================
# 2. INICIALIZAÇÃO DOS MODELOS (LBPH e Cascades)
# ==============================================================================
print("[SISTEMA] Carregando Modelos de Face...")
cascade_frente = cv2.CascadeClassifier(
    cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
cascade_perfil = cv2.CascadeClassifier(
    cv2.data.haarcascades + 'haarcascade_profileface.xml')
recognizer = cv2.face.LBPHFaceRecognizer_create()


def inicializar_banco():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute(
            "CREATE TABLE IF NOT EXISTS alunos (id INT PRIMARY KEY, nome VARCHAR(100))")
        cursor.execute(
            "CREATE TABLE IF NOT EXISTS amostras_facial (id INT AUTO_INCREMENT PRIMARY KEY, aluno_id INT, imagem LONGBLOB)")
        conn.commit()
        conn.close()
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
            print(f"[TREINO] Reconhecimento pronto ({len(faces)} faces).")
        else:
            modelo_treinado = False
            print("[TREINO] Nenhuma face encontrada no banco.")
        conn.close()
    except Exception as e:
        print(f"[ERRO TREINO] {e}")

# ==============================================================================
# 3. NÚCLEO DE PROCESSAMENTO (OTIMIZADO)
# ==============================================================================


def capturar_camera():
    global frame_raw, camera_ativa
    cap = None
    while True:
        if camera_ativa:
            if cap is None:
                cap = cv2.VideoCapture(0)
                if not cap.isOpened():
                    print("[ERRO] Nao foi possivel abrir a camera FACIAL.")
                    cap = None
                    camera_ativa = False
                    continue
                cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                print("[SISTEMA] Webcam FACIAL aberta.")

            ret, frame = cap.read()
            if ret:
                frame_raw = frame
            else:
                time.sleep(0.01)
        else:
            if cap is not None:
                cap.release()
                cap = None
                frame_raw = None
                print("[SISTEMA] Webcam FACIAL liberada.")
            time.sleep(0.1)


def processar_video():
    global frame_raw, frame_processado, foco_nome, foco_cor
    global modo_cadastro, cadastro_count, cad_id, cad_nome

    ultima_caixa = None
    ultimo_nome = "Buscando rosto..."
    ultima_cor = (0, 0, 255)
    frames_perdidos = 0
    MAX_PERSISTENCIA = 30  # ~1 segundo de memória

    alpha_atual = 0.0      # Para efeito de Fade In/Out
    skip_frames = 0

    while True:
        if not camera_ativa or frame_raw is None:
            time.sleep(0.05)
            continue

        # 1. Captura e Downsampling.
        frame = frame_raw.copy()
        h_orig, w_orig = frame.shape[:2]

        # 2. IA Processamento (320px)
        w_proc = 320
        scale = w_orig / w_proc
        small_frame = cv2.resize(frame, (w_proc, int(h_orig / scale)))
        gray = cv2.cvtColor(small_frame, cv2.COLOR_BGR2GRAY)

        # 3. Detecção com Memória
        rosto_encontrado = None
        if skip_frames <= 0:
            caixas = list(cascade_frente.detectMultiScale(gray, 1.3, 5))
            if not caixas:
                caixas = list(cascade_perfil.detectMultiScale(gray, 1.3, 5))

            if caixas:
                rosto_encontrado = max(caixas, key=lambda b: b[2]*b[3])
                ultima_caixa = rosto_encontrado
                frames_perdidos = 0
                alpha_atual = min(alpha_atual + 0.2, 1.0)  # Fade In rápido
            else:
                frames_perdidos += 1
                if frames_perdidos > MAX_PERSISTENCIA:
                    ultima_caixa = None
                    alpha_atual = max(alpha_atual - 0.1, 0.0)  # Fade Out suave
            skip_frames = 2
        else:
            skip_frames -= 1

        # 4. PREPARAÇÃO VISUAL (Fundo sempre desfocado)
        # Desfoque super rápido (escala reduzida)
        small_blur = cv2.resize(frame, (0, 0), fx=0.3, fy=0.3)
        blur_img = cv2.GaussianBlur(small_blur, (15, 15), 0)
        blur_full = cv2.resize(blur_img, (w_orig, h_orig))

        # 5. MÁSCARA DE FOCO (Alpha Blending)
        mask = np.zeros((h_orig, w_orig), dtype=np.uint8)

        if ultima_caixa is not None:
            rx, ry, rw, rh = [int(c * scale) for c in ultima_caixa]
            center = (rx + rw // 2, ry + rh // 2)
            axes = (int(rw * 0.8), int(rh * 1.1))
            cv2.ellipse(mask, center, axes, 0, 0, 360, 255, -1)

            # Reconhecimento Facial (Aprimorado com Equalização)
            if rosto_encontrado is not None and not modo_cadastro and modelo_treinado:
                roi = gray[rosto_encontrado[1]:rosto_encontrado[1]+int(
                    rh*0.6/scale), rosto_encontrado[0]:rosto_encontrado[0]+rosto_encontrado[2]]
                if roi.size > 0:
                    roi_proc = cv2.resize(roi, (200, 200))
                    # <--- MELHORIA: Equalização de Histograma
                    roi_proc = cv2.equalizeHist(roi_proc)
                    uid, conf = recognizer.predict(roi_proc)
                    if conf < LIMITE_CONFIANCA_FACE:
                        ultimo_nome = nomes_conhecidos.get(uid, f"ID {uid}")
                        ultima_cor = (0, 255, 0)
                    else:
                        ultimo_nome = "Desconhecido"
                        ultima_cor = (0, 0, 255)

                    # Sincroniza com as globais para a API /status_ia
                    foco_nome = ultimo_nome
                    foco_cor = ultima_cor

            # Lógica de Cadastro (25 Frames)
            if modo_cadastro and rosto_encontrado is not None:
                if cadastro_count < 25:
                    cadastro_count += 1
                    face_save = gray[rosto_encontrado[1]:rosto_encontrado[1]+int(
                        rh*0.6/scale), rosto_encontrado[0]:rosto_encontrado[0]+rosto_encontrado[2]]
                    # Equalização na fase de treino facilita o reconhecimento depois
                    face_save = cv2.resize(face_save, (200, 200))
                    face_save = cv2.equalizeHist(face_save)

                    threading.Thread(target=salvar_assincrono, args=(
                        face_save, cad_id, cad_nome)).start()
                    ultimo_nome = f"Capturando {cadastro_count}/25"
                    ultima_cor = (255, 165, 0)
                else:
                    modo_cadastro = False
                    treinar_modelo()
                    cadastro_count = 0
            foco_nome = ultimo_nome
            foco_cor = ultima_cor
        else:
            foco_nome = "Desconhecido"
            foco_cor = (0, 0, 255)

        # Mescla final (Blur suave)
        mask_3d = cv2.GaussianBlur(mask, (31, 31), 0).astype(float) / 255.0
        mask_3d = cv2.merge([mask_3d, mask_3d, mask_3d])
        result = (frame * mask_3d + blur_full * (1 - mask_3d)).astype(np.uint8)

        # Texto sobre o frame
        if ultima_caixa is not None:
            rx, ry, rw, rh = [int(c * scale) for c in ultima_caixa]
            cv2.putText(result, ultimo_nome, (rx, ry-10),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.7, ultima_cor, 2)
            cv2.rectangle(result, (rx, ry), (rx+rw, ry+rh), ultima_cor, 1)

        with lock_frame:
            frame_processado = result


def salvar_assincrono(face_img, uid, nome):
    try:
        if face_img.size == 0:
            return
        face_res = cv2.resize(face_img, (200, 200))
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO alunos (id, nome) VALUES (%s, %s) ON DUPLICATE KEY UPDATE nome=%s", (uid, nome, nome))
        _, buf = cv2.imencode('.jpg', face_res)
        cursor.execute(
            "INSERT INTO amostras_facial (aluno_id, imagem) VALUES (%s, %s)", (uid, buf.tobytes()))
        conn.commit()
        conn.close()
    except:
        pass


def gerar_stream():
    while True:
        if not camera_ativa or frame_processado is None:
            time.sleep(0.05)
            continue

        _, buffer = cv2.imencode('.jpg', frame_processado, [
                                 cv2.IMWRITE_JPEG_QUALITY, 70])
        yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
        time.sleep(0.03)

# ==============================================================================
# 4. ROTAS FLASK
# ==============================================================================


@app.route('/status_ia')
def status_ia():
    return jsonify({"nome": foco_nome, "cadastro_em_andamento": modo_cadastro})


@app.route('/video_feed')
def video_feed():
    return Response(gerar_stream(), mimetype='multipart/x-mixed-replace; boundary=frame')


@app.route('/ligar')
def ligar():
    global camera_ativa
    camera_ativa = True
    return {"status": "ok"}


@app.route('/desligar')
def desligar():
    global camera_ativa, frame_processado, frame_raw
    camera_ativa = False
    time.sleep(0.2)
    frame_processado = None
    frame_raw = None
    return {"status": "ok"}


@app.route('/solicitar_cadastro', methods=['POST'])
def solicitar():
    global modo_cadastro, cadastro_count, cad_id, cad_nome
    d = request.json
    cad_id, cad_nome = d.get('id'), d.get('nome')
    cadastro_count = 0
    modo_cadastro = True
    return {"status": "ok"}


if __name__ == '__main__':
    inicializar_banco()
    treinar_modelo()
    threading.Thread(target=capturar_camera, daemon=True).start()
    threading.Thread(target=processar_video, daemon=True).start()
    app.run(host='0.0.0.0', port=5001, debug=False, threaded=True)
