<?php
require_once 'database.php';
header('Content-Type: application/json');

$data = $_GET['data'] ?? date('Y-m-d');
$cursoId = 1;

try {
    $stmt = $pdo->prepare("
        SELECT o.data_hora, a.nome as aluno, GROUP_CONCAT(e.nome) as epis, o.status
        FROM ocorrencias o
        JOIN alunos a ON o.aluno_id = a.id
        JOIN ocorrencia_epis oe ON o.id = oe.ocorrencia_id
        JOIN epis e ON oe.epi_id = e.id
        WHERE a.curso_id = ? AND DATE(o.data_hora) = ?
        GROUP BY o.id
        ORDER BY o.data_hora DESC
    ");
    $stmt->execute([$cursoId, $data]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}