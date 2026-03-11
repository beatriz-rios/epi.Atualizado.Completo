<?php
// Limpa qualquer espaço em branco ou erro acidental para não corromper a imagem
ob_clean(); 

// Importa a ligação com o banco de dados (que agora usa a variável $conn)
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) {
    exit;
}

$id = (int)$_GET['id'];

// Sistema de Cache para deixar a página super rápida
$segundos_cache = 30 * 24 * 60 * 60; 
header("Cache-Control: max-age=$segundos_cache, public");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + $segundos_cache) . ' GMT');
header("Pragma: cache");

// BUSCA A IMAGEM NO BANCO (AQUI ESTAVA O ERRO, AGORA USA $conn)
$stmt = $conn->prepare("SELECT imagem FROM evidencias WHERE ocorrencia_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Se encontrar a imagem, transforma a página num arquivo JPEG e mostra
if ($row = $result->fetch_assoc()) {
    if (!empty($row['imagem'])) {
        header("Content-Type: image/jpeg");
        echo $row['imagem'];
    }
}
?>