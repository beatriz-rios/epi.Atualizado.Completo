<?php
// config/database.php

$host = "localhost";
$db = "epi_guard";
$user = "root";
$pass = "";
$port = 3306;


// Cria a conexão usando MySQLi (Mais leve que PDO)
$conn = mysqli_connect($host, $user, $pass, $db, $port);

// Verifica se houve erro
if (!$conn) {
    error_log("Falha na conexão: " . mysqli_connect_error());
    die("Erro interno de conexão com o banco de dados.");
}

// Define o charset para evitar problemas com acentos
mysqli_set_charset($conn, "utf8mb4");

?>