<?php
session_start();
// O arquivo database.php agora deve conter a conexão $conn usando mysqli_connect
require_once 'database.php';


$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($usuario) || empty($senha)) {
    header("Location: ../php/index.php?erro=campos");
    exit;
}

// Busca o usuário no banco usando MySQLi
$sql = "SELECT id, nome, usuario, senha, cargo, id_curso 
        FROM usuarios 
        WHERE usuario = ? 
        LIMIT 1";

// Prepara a query
$stmt = mysqli_prepare($conn, $sql);

// "s" indica que o parâmetro é uma string
mysqli_stmt_bind_param($stmt, "s", $usuario);
mysqli_stmt_execute($stmt);

// Obtém o resultado
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// --- AQUI ESTÁ A MUDANÇA ---
// Comparação de texto puro (Lembrando: isso é menos seguro que password_verify)
if ($user && $senha == $user['senha']) {

    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['cargo'] = $user['cargo'];
    $_SESSION['usuario_id_curso'] = $user['id_curso'];

    header("Location: ../php/dashboard.php");
    exit;

}
else {
    header("Location: ../php/index.php?erro=login");
    exit;
}