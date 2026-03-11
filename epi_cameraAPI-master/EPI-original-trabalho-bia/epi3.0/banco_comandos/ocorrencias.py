# com esse arquivo vc pode copualar o banco com ocrrencias falsas


import mysql.connector
import random
from datetime import datetime, timedelta

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",          # coloque sua senha se tiver
    database="epi_guard",
    port = 3308  # nome do seu banco
)

cursor = conn.cursor()

# Buscar IDs reais dos alunos (evita erro de FK)
cursor.execute("SELECT id FROM alunos")
alunos_ids = [row[0] for row in cursor.fetchall()]

if not alunos_ids:
    print("Não existem alunos cadastrados.")
    exit()

inicio_ano = datetime(2026, 1, 1)
fim_ano = datetime(2026, 12, 31, 23, 59, 59)
intervalo = fim_ano - inicio_ano

for i in range(500):
    aluno_id = random.choice(alunos_ids)
    epi_id = random.choice([1, 2])

    # gera data aleatória dentro de 2026
    segundos_aleatorios = random.randint(0, int(intervalo.total_seconds()))
    data_aleatoria = inicio_ano + timedelta(seconds=segundos_aleatorios)

    cursor.execute(
        "INSERT INTO ocorrencias (aluno_id, data_hora, epi_id) VALUES (%s, %s, %s)",
        (aluno_id, data_aleatoria, epi_id)
    )

conn.commit()
cursor.close()
conn.close()

print("500 registros inseridos com datas aleatórias em 2026.")