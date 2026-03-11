<?php
// Configura a duração do cookie da sessão para 24 horas
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detecta se é requisição AJAX ou API
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
    (strpos($_SERVER['REQUEST_URI'], '/apis/') !== false);

// Timeout de sessão (aumentado para 24h a pedido do usuário)
if (isset($_SESSION['last_activity']) &&
(time() - $_SESSION['last_activity'] > 86400)) {

    session_unset();
    session_destroy();

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'session_expired']);
        exit;
    }
    else {
        header("Location: index.php");
        exit;
    }
}

// Verifica login
if (!isset($_SESSION['usuario_id'])) {

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'not_logged']);
        exit;
    }
    else {
        header("Location: index.php");
        exit;
    }
}

// Atualiza atividade
$_SESSION['last_activity'] = time();
?>