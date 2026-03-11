import mysql.connector
from mysql.connector import Error

def criar_banco():
    try:
        # Conexão sem especificar banco (para poder criar o schema)
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            port = 3308  # coloque sua senha aqui se tiver
        )

        cursor = conn.cursor()

        # Criar banco
        cursor.execute("CREATE DATABASE IF NOT EXISTS epi_guard CHARACTER SET utf8mb4;")
        cursor.execute("USE epi_guard;")

        # =========================
        # Tabela cursos
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS cursos (
            id INT NOT NULL AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            sigla VARCHAR(10),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela alunos
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS alunos (
            id INT NOT NULL AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            curso_id INT,
            turno VARCHAR(50),
            foto_referencia VARCHAR(255),
            imagem LONGBLOB,
            PRIMARY KEY (id),
            INDEX (curso_id),
            FOREIGN KEY (curso_id) REFERENCES cursos(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela epis
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS epis (
            id INT NOT NULL AUTO_INCREMENT,
            nome VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela ocorrencias
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS ocorrencias (
            id INT NOT NULL AUTO_INCREMENT,
            aluno_id INT NOT NULL,
            data_hora DATETIME NOT NULL,
            epi_id INT NOT NULL,
            PRIMARY KEY (id),
            INDEX (aluno_id),
            INDEX (epi_id),
            FOREIGN KEY (aluno_id) REFERENCES alunos(id),
            FOREIGN KEY (epi_id) REFERENCES epis(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela usuarios
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT NOT NULL AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            usuario VARCHAR(50) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            cargo ENUM('super_admin', 'supervisor', 'professor') NOT NULL,
            turno VARCHAR(50),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela acoes_ocorrencia
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS acoes_ocorrencia (
            id INT NOT NULL AUTO_INCREMENT,
            ocorrencia_id INT NOT NULL,
            tipo ENUM('obs', 'adv_verbal', 'adv_escrita', 'suspensao'),
            observacao TEXT,
            usuario_id INT NOT NULL,
            data_hora DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX (ocorrencia_id),
            INDEX (usuario_id),
            FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela amostras_facial
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS amostras_facial (
            id INT NOT NULL AUTO_INCREMENT,
            aluno_id INT,
            imagem LONGBLOB,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela evidencias
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS evidencias (
            id INT NOT NULL AUTO_INCREMENT,
            ocorrencia_id INT,
            imagem LONGBLOB,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        # =========================
        # Tabela ocorrencia_epis
        # =========================
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS ocorrencia_epis (
            id INT NOT NULL AUTO_INCREMENT,
            ocorrencia_id INT NOT NULL,
            epi_id INT NOT NULL,
            PRIMARY KEY (id),
            INDEX (ocorrencia_id),
            INDEX (epi_id),
            FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id),
            FOREIGN KEY (epi_id) REFERENCES epis(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)

        conn.commit()
        print("Banco de dados e tabelas criados com sucesso.")

    except Error as e:
        print("Erro:", e)

    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()




def inserir_epis():
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        port=3308,
        database="epi_guard"
    )

    cursor = conn.cursor()

    epis_para_inserir = [
        (1, 'oculos'),
        (2, 'capacete')
    ]

    cursor.executemany(
        "INSERT IGNORE INTO epis (id, nome) VALUES (%s, %s)",
        epis_para_inserir
    )

    conn.commit()
    cursor.close()
    conn.close()

if __name__ == "__main__":
    criar_banco()
    inserir_epis()