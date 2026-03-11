<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Pega o last_id enviado pelo JavaScript (padrão é 0 na primeira vez)
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : -1;

    // Se for a primeira carga (init), apenas pegamos o último ID do banco
    if ($last_id === 0) {
        $stmt = $pdo->query("SELECT MAX(id) FROM ocorrencias");
        $maxId = (int) $stmt->fetchColumn();
        
        echo json_encode([
            'status' => 'init',
            // Se o banco estiver vazio, manda -1 para não ficar preso num loop
            'last_id' => $maxId > 0 ? $maxId : -1 
        ]);
        exit;
    }

    // Se o banco estava vazio no init, ajustamos para 0 para buscar as novas
    if ($last_id === -1) {
        $last_id = 0;
    }

    // Buscamos as ocorrências novas
    // CORREÇÃO: Removido o filtro de curso_id que não existia e adicionado a o.data_hora
    $query = "SELECT o.id, a.nome AS aluno, o.data_hora,
                     CASE o.epi_id
                        WHEN 1 THEN 'Óculos de Proteção'
                        WHEN 2 THEN 'Capacete de Segurança'
                        ELSE 'EPI não identificado'
                     END AS epi_nome
              FROM ocorrencias o
              LEFT JOIN alunos a ON a.id = o.aluno_id
              WHERE o.id > ?
              ORDER BY o.id ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$last_id]);
    
    $novasOcorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($novasOcorrencias) > 0) {
        echo json_encode([
            'status' => 'success',
            'dados' => $novasOcorrencias
        ]);
    } else {
        echo json_encode([
            'status' => 'no_new'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;