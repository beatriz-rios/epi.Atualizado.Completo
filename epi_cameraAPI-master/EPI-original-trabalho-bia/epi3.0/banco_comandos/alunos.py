import mysql.connector

# ===== CONFIGURE AQUI =====
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",          # coloque sua senha se tiver
    database="epi_guard",
    port = 3308  # nome do seu banco
)

cursor = conn.cursor()

alunos = [
    "Arthur",
    "Beatriz",
    "Gideao",
    "Ian ",
    "Pietra",
    "Pirra",
    "Josue Benevides",
    "Kauan Bonfin",
    "Lais Uedes",
    "Nahyron",
    "Joiao",
    "Black",
    "Vitor",
    "Miguel",
    "Ruan",
    "Latorre",
    "Arthur Silva",
    "Beatriz Souza",
    "Ian Costa",
    "Pietra Lima"
]

for nome in alunos:
    cursor.execute(
        "INSERT INTO alunos (nome,curso_id, turno) VALUES (%s,1, %s)",
        (nome, "manha")
    )

conn.commit()

cursor.close()
conn.close()

print("20 alunos inseridos com sucesso.")