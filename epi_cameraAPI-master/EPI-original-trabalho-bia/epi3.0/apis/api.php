<?php
// =================================================================================
// ARQUIVO: apis/api.php (CORRIGIDO PARA MYSQLI - SEM PDO)
// =================================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php'; // Proteção de sessão

// Limpa buffer para evitar erros de JSON
if (ob_get_length())
    ob_clean();

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1); // Temporário para debug

$cursoId = (isset($_SESSION['usuario_id_curso']) && (int)$_SESSION['usuario_id_curso'] > 0) ? (int)$_SESSION['usuario_id_curso'] : 1;

try {
    $action = $_GET['action'] ?? '';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $date = $_GET['date'] ?? date('Y-m-d');

    // Função auxiliar para formatar array de meses
    function formatMonthArray($result)
    {
        $arr = array_fill(0, 12, 0);
        while ($r = mysqli_fetch_assoc($result)) {
            $idx = (int)$r['mes'] - 1;
            if ($idx >= 0 && $idx < 12) {
                $arr[$idx] = (int)$r['qtd'];
            }
        }
        return $arr;
    }

    // 1. GRÁFICOS (BARRAS E ROSCA)
    if ($action === 'charts') {
        // A) Barras - Capacete (ID 2)
        $sql = "SELECT MONTH(o.data_hora) as mes, COUNT(*) as qtd 
                FROM ocorrencias o JOIN alunos a ON a.id = o.aluno_id 
                WHERE o.epi_id = 2 AND YEAR(o.data_hora) = ? AND a.curso_id = ? 
                GROUP BY mes";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $year, $cursoId);
        mysqli_stmt_execute($stmt);
        $capaceteArr = formatMonthArray(mysqli_stmt_get_result($stmt));

        // B) Barras - Óculos (ID 1)
        $sql = "SELECT MONTH(o.data_hora) as mes, COUNT(*) as qtd 
                FROM ocorrencias o JOIN alunos a ON a.id = o.aluno_id 
                WHERE o.epi_id = 1 AND YEAR(o.data_hora) = ? AND a.curso_id = ? 
                GROUP BY mes";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $year, $cursoId);
        mysqli_stmt_execute($stmt);
        $oculosArr = formatMonthArray(mysqli_stmt_get_result($stmt));

        // C) Total Geral
        $sql = "SELECT MONTH(o.data_hora) as mes, COUNT(*) as qtd 
                FROM ocorrencias o JOIN alunos a ON a.id = o.aluno_id 
                WHERE YEAR(o.data_hora) = ? AND a.curso_id = ? 
                GROUP BY mes";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $year, $cursoId);
        mysqli_stmt_execute($stmt);
        $totalArr = formatMonthArray(mysqli_stmt_get_result($stmt));

        // D) Rosca - Por Tipo de EPI
        $sql = "SELECT e.nome, COUNT(*) as qtd FROM ocorrencias o 
                JOIN epis e ON e.id = o.epi_id 
                JOIN alunos a ON a.id = o.aluno_id
                WHERE YEAR(o.data_hora) = ? AND a.curso_id = ? 
                GROUP BY e.nome";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $year, $cursoId);
        mysqli_stmt_execute($stmt);
        $resDoughnut = mysqli_stmt_get_result($stmt);

        $labels = [];
        $dataDoughnut = [];
        while ($d = mysqli_fetch_assoc($resDoughnut)) {
            $labels[] = $d['nome'];
            $dataDoughnut[] = (int)$d['qtd'];
        }

        echo json_encode([
            'bar' => ['capacete' => $capaceteArr, 'oculos' => $oculosArr, 'total' => $totalArr],
            'doughnut' => ['labels' => $labels, 'data' => $dataDoughnut]
        ]);
        exit;
    }

    // 2. CALENDÁRIO
    if ($action === 'calendar') {
        $sql = "SELECT o.data_hora as full_date, a.nome AS name, e.nome AS `desc`, DATE_FORMAT(o.data_hora, '%H:%i') AS time
                FROM ocorrencias o
                JOIN alunos a ON o.aluno_id = a.id
                LEFT JOIN epis e ON e.id = o.epi_id
                WHERE MONTH(o.data_hora) = ? AND YEAR(o.data_hora) = ? AND a.curso_id = ?
                ORDER BY o.data_hora ASC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $month, $year, $cursoId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
        exit;
    }

    // 3. MODAL (DETALHES)
    if ($action === 'modal_details') {
        $mesSQL = ($month == 0) ? 1 : $month;
        $epiFilter = $_GET['epi'] ?? '';

        $sql = "SELECT o.id AS ocorrencia_id, DATE_FORMAT(o.data_hora, '%d/%m/%Y') AS data, a.nome AS aluno, a.id AS aluno_id, c.nome AS curso,
                       COALESCE(e.nome, 'Não informado') AS epis, DATE_FORMAT(o.data_hora, '%H:%i') AS hora,
                       CASE WHEN ac.id IS NOT NULL THEN 'Resolvido' ELSE 'Pendente' END AS status_formatado
                FROM ocorrencias o
                JOIN alunos a ON a.id = o.aluno_id
                LEFT JOIN cursos c ON c.id = a.curso_id
                LEFT JOIN epis e ON e.id = o.epi_id
                LEFT JOIN acoes_ocorrencia ac ON ac.ocorrencia_id = o.id
                WHERE MONTH(o.data_hora) = ? AND YEAR(o.data_hora) = ? AND a.curso_id = ? ";

        if (!empty($epiFilter)) {
            $sql .= " AND e.nome = ? ";
        }

        $sql .= " GROUP BY o.id ORDER BY o.data_hora DESC";

        $stmt = mysqli_prepare($conn, $sql);
        if (!empty($epiFilter)) {
            mysqli_stmt_bind_param($stmt, "iiis", $mesSQL, $year, $cursoId, $epiFilter);
        }
        else {
            mysqli_stmt_bind_param($stmt, "iii", $mesSQL, $year, $cursoId);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
        exit;
    }

    // 4. CONTADOR DE NOTIFICAÇÕES (Pendentes)
    if ($action === 'notification_count') {
        $seenId = (int)($_GET['seen_id'] ?? 0);
        $withDetails = isset($_GET['details']) && $_GET['details'] == '1';

        // Conta quantos tem ID > que o visto pelo usuário
        $sqlCount = "SELECT COUNT(o.id) as total, MAX(o.id) as max_id 
                     FROM ocorrencias o 
                     JOIN alunos a ON a.id = o.aluno_id 
                     WHERE a.curso_id = ? AND o.id > ?";

        $stmt = mysqli_prepare($conn, $sqlCount);
        mysqli_stmt_bind_param($stmt, "ii", $cursoId, $seenId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        $details = [];
        if ($withDetails && ($row['total'] ?? 0) > 0) {
            $sqlDetails = "SELECT a.nome as aluno, e.nome as epi, o.id 
                           FROM ocorrencias o 
                           JOIN alunos a ON a.id = o.aluno_id 
                           JOIN epis e ON e.id = o.epi_id 
                           WHERE a.curso_id = ? AND o.id > ? 
                           ORDER BY o.id DESC LIMIT 3";
            $stmtD = mysqli_prepare($conn, $sqlDetails);
            mysqli_stmt_bind_param($stmtD, "ii", $cursoId, $seenId);
            mysqli_stmt_execute($stmtD);
            $resD = mysqli_stmt_get_result($stmtD);
            while ($d = mysqli_fetch_assoc($resD)) {
                $details[] = $d;
            }
        }

        echo json_encode([
            'count' => (int)($row['total'] ?? 0),
            'max_id' => (int)($row['max_id'] ?? 0),
            'new_items' => $details
        ]);
        exit;
    }

    // 5. RESOLVER OCORRÊNCIA (Assinar)
    if ($action === 'resolve_occurrence') {
        $ocorrenciaId = (int)($_POST['ocorrencia_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'obs';
        $observacao = $_POST['observacao'] ?? '';
        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        if ($ocorrenciaId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de ocorrência inválido ou não fornecido.']);
            exit;
        }

        // Insere na tabela de ações para mudar o status para 'Resolvido'/'Confirmado'
        $sql = "INSERT INTO acoes_ocorrencia (ocorrencia_id, tipo, observacao, usuario_id, data_hora) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issi", $ocorrenciaId, $tipo, $observacao, $usuarioId);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar: ' . mysqli_error($conn)]);
        }
        exit;
    }

    // 5. LISTA DE ALUNOS
    if (empty($action)) {
        $sqlAlunos = "SELECT a.id, a.nome, c.nome as curso_nome FROM alunos a 
                      LEFT JOIN cursos c ON c.id = a.curso_id 
                      WHERE a.curso_id = ?";
        $stmtAlunos = mysqli_prepare($conn, $sqlAlunos);
        mysqli_stmt_bind_param($stmtAlunos, "i", $cursoId);
        mysqli_stmt_execute($stmtAlunos);
        $resAlunos = mysqli_stmt_get_result($stmtAlunos);
        $resultado = [];

        while ($aluno = mysqli_fetch_assoc($resAlunos)) {
            // Risco Hoje
            $sqlRisco = "SELECT COUNT(*) as total FROM ocorrencias o JOIN alunos a ON a.id = o.aluno_id WHERE o.aluno_id = ? AND DATE(o.data_hora) = CURDATE() AND a.curso_id = ?";
            $stmtRisco = mysqli_prepare($conn, $sqlRisco);
            mysqli_stmt_bind_param($stmtRisco, "ii", $aluno['id'], $cursoId);
            mysqli_stmt_execute($stmtRisco);
            $temRisco = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtRisco))['total'] > 0;

            // Histórico
            $sqlHist = "SELECT COUNT(*) as total FROM ocorrencias o JOIN alunos a ON a.id = o.aluno_id WHERE o.aluno_id = ? AND a.curso_id = ?";
            $stmtHist = mysqli_prepare($conn, $sqlHist);
            mysqli_stmt_bind_param($stmtHist, "ii", $aluno['id'], $cursoId);
            mysqli_stmt_execute($stmtHist);
            $temHistorico = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtHist))['total'] > 0;

            // EPIs faltando hoje
            $missing = [];
            if ($temRisco) {
                $sqlEpi = "SELECT e.nome FROM ocorrencias o JOIN epis e ON e.id = o.epi_id JOIN alunos a ON a.id = o.aluno_id WHERE o.aluno_id = ? AND DATE(o.data_hora) = CURDATE() AND a.curso_id = ?";
                $stmtEpi = mysqli_prepare($conn, $sqlEpi);
                mysqli_stmt_bind_param($stmtEpi, "ii", $aluno['id'], $cursoId);
                mysqli_stmt_execute($stmtEpi);
                $resEpi = mysqli_stmt_get_result($stmtEpi);
                while ($rowEpi = mysqli_fetch_assoc($resEpi)) {
                    $missing[] = $rowEpi['nome'];
                }
            }

            $resultado[] = [
                'id' => $aluno['id'],
                'name' => $aluno['nome'],
                'course' => $aluno['curso_nome'],
                'missing' => $missing,
                'history' => $temHistorico
            ];
        }
        echo json_encode($resultado);
        exit;
    }
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>