<?php
// =================================================================================
// ARQUIVO: apis/controle.api.php (CONVERTIDO PARA MYSQLI - SEM PDO)
// =================================================================================

require_once __DIR__ . '/../config/database.php';

// Limpa qualquer saída anterior (espaços em branco, erros) para não quebrar o JSON
if (ob_get_length()) ob_clean();

header('Content-Type: application/json; charset=utf-8');
// Desativa exibição de erros no corpo da resposta
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // 1. BUSCAR ALUNOS COM O NOME DO CURSO
    $sql = "
        SELECT 
            a.id, 
            a.nome, 
            c.nome AS curso_nome
        FROM alunos a
        LEFT JOIN cursos c ON a.curso_id = c.id
        ORDER BY a.nome ASC
    ";
    
    $resAlunos = mysqli_query($conn, $sql);
    
    if (!$resAlunos) {
        throw new Exception("Erro na consulta de alunos: " . mysqli_error($conn));
    }

    $resultado = [];

    // Usando mysqli_fetch_assoc para percorrer os resultados de forma leve
    while ($aluno = mysqli_fetch_assoc($resAlunos)) {
        $id = $aluno['id'];

        // 2. CONTA O TOTAL DE OCORRÊNCIAS (Histórico Geral)
        $sqlCount = "SELECT COUNT(*) as total FROM ocorrencias WHERE aluno_id = ?";
        $stmtCount = mysqli_prepare($conn, $sqlCount);
        mysqli_stmt_bind_param($stmtCount, "i", $id);
        mysqli_stmt_execute($stmtCount);
        $resCount = mysqli_stmt_get_result($stmtCount);
        $totalOcorrencias = mysqli_fetch_assoc($resCount)['total'] ?? 0;

        // 3. VERIFICA SE FALTOU EPI HOJE (Risco Ativo)
        $sqlEpi = "
            SELECT e.nome 
            FROM ocorrencias o 
            JOIN epis e ON e.id = o.epi_id 
            WHERE o.aluno_id = ? AND DATE(o.data_hora) = CURDATE()
        ";
        $stmtEpi = mysqli_prepare($conn, $sqlEpi);
        mysqli_stmt_bind_param($stmtEpi, "i", $id);
        mysqli_stmt_execute($stmtEpi);
        $resEpi = mysqli_stmt_get_result($stmtEpi);
        
        $episFaltantesHoje = [];
        while ($rowEpi = mysqli_fetch_assoc($resEpi)) {
            $episFaltantesHoje[] = $rowEpi['nome'];
        }

        $resultado[] = [
            'id'            => (int)$aluno['id'],
            'name'          => $aluno['nome'],
            'course'        => $aluno['curso_nome'] ?? 'Sem Curso',
            'missing'       => $episFaltantesHoje,
            'history_count' => (int)$totalOcorrencias
        ];
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>