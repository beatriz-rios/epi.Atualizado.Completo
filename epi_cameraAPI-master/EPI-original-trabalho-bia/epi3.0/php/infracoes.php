<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// ==========================================
// 1. LÓGICA DE FILTROS (BACK-END)
// ==========================================
$cursoId = (isset($_SESSION['usuario_id_curso']) && (int)$_SESSION['usuario_id_curso'] > 0) ? (int)$_SESSION['usuario_id_curso'] : 1;
$filtroData = $_GET['periodo'] ?? ($_GET['filtro'] ?? 'hoje');
$filtroEpi = isset($_GET['epi']) ? $_GET['epi'] : '';

try {
    // 1.1 Busca lista de EPIs para o select (MySQLi)
    $resultEpis = $conn->query("SELECT id, nome FROM epis ORDER BY nome ASC");
    $listaEpis = [];
    while ($rowEpi = $resultEpis->fetch_assoc()) {
        $listaEpis[] = $rowEpi;
    }

    // 1.2 Montagem da Query Principal (Filtrada por Curso do Usuário)
    $sql = "
        SELECT 
            o.id, 
            o.data_hora,
            a.nome AS aluno_nome,
            a.id AS aluno_id,
            c.nome AS aluno_curso,
            e.nome AS epi_nome,
            ev.imagem AS foto_caminho,
            CASE WHEN ac.id IS NOT NULL THEN 1 ELSE 0 END AS is_assinada
        FROM ocorrencias o
        JOIN alunos a ON a.id = o.aluno_id
        LEFT JOIN cursos c ON c.id = a.curso_id
        JOIN epis e ON e.id = o.epi_id
        LEFT JOIN evidencias ev ON ev.ocorrencia_id = o.id 
        LEFT JOIN acoes_ocorrencia ac ON ac.ocorrencia_id = o.id
        WHERE a.curso_id = ? AND o.oculto = 0
    ";

    // Filtros de Data
    if ($filtroData == 'hoje' || $filtroData == 'dia') {
        $sql .= " AND DATE(o.data_hora) = CURDATE()";
    } elseif ($filtroData == '7dias' || $filtroData == 'semana') {
        $sql .= " AND o.data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filtroData == '30dias' || $filtroData == 'mes') {
        $sql .= " AND o.data_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Filtro de EPI
    if (!empty($filtroEpi)) {
        $sql .= " AND o.epi_id = ?";
    }

    $sql .= " GROUP BY o.id ORDER BY o.data_hora DESC LIMIT 100";

    // 1.3 Execução com Prepared Statement (MySQLi)
    $stmt = $conn->prepare($sql);

    if (!empty($filtroEpi)) {
        $stmt->bind_param("ii", $cursoId, $filtroEpi);
    } else {
        $stmt->bind_param("i", $cursoId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $infracoes = [];
    while ($row = $result->fetch_assoc()) {
        $infracoes[] = $row;
    }
} catch (Exception $e) {
    $infracoes = [];
    $listaEpis = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPI Guard | Infrações</title>
    <link rel="stylesheet" href="../css/infracoes.css">
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="../css/transitions.css">

    <script src="../js/Dark.js"></script>
    <script src="../js/transitions.js"></script>
</head>

<body>
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <div class="header-container">
                <div class="page-title">
                    <h1>Painel Geral</h1>
                    <p>Monitoramento de Segurança</p>
                </div>

                <!-- Removido ações do header a pedido do usuário -->


                <form method="GET" class="header-controls">
                    <div class="filters-row">
                        <select name="periodo" class="filter-select" onchange="this.form.submit()">
                            <option value="hoje" <?php echo ($filtroData == 'hoje' || $filtroData == 'dia') ? 'selected' : ''; ?>>Hoje</option>
                            <option value="7dias" <?php echo ($filtroData == '7dias' || $filtroData == 'semana') ? 'selected' : ''; ?>>Últimos 7 dias</option>
                            <option value="30dias" <?php echo ($filtroData == '30dias' || $filtroData == 'mes') ? 'selected' : ''; ?>>Últimos 30 dias</option>
                            <option value="todos" <?php echo $filtroData == 'todos' ? 'selected' : ''; ?>>Tudo</option>
                        </select>

                        <select name="epi" class="filter-select" onchange="this.form.submit()">
                            <option value="">Todos os EPIs</option>
                            <?php foreach ($listaEpis as $epi): ?>
                                <option value="<?php echo $epi['id']; ?>" <?php echo $filtroEpi == $epi['id'] ? 'selected' : ''; ?>>
                                    Apenas <?php echo htmlspecialchars($epi['nome']); ?>
                                </option>
                            <?php
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="search-container-full">
                        <div class="search-wrapper-animated">
                            <i data-lucide="search" class="search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Buscar por aluno, curso ou infração...">
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <div class="gallery-container">
            <div class="grid-cards" id="cardsContainer">
                <?php if (empty($infracoes)): ?>
                    <p style="padding:20px; color:#666;">Nenhuma infração encontrada.</p>
                <?php
                else: ?>
                    <?php foreach ($infracoes as $item):
                        $imgSrc = "mostrar_imagem.php?id=" . $item['id'];
                        $nomeSafe = htmlspecialchars($item['aluno_nome'] ?? 'Desconhecido', ENT_QUOTES);
                        $epiSafe = htmlspecialchars($item['epi_nome'] ?? 'EPI', ENT_QUOTES);
                        $setorSafe = htmlspecialchars($item['aluno_curso'] ?? 'Geral', ENT_QUOTES);
                        $dataObj = new DateTime($item['data_hora']);
                        $horaF = $dataObj->format('H:i');
                        $dataF = $dataObj->format('d/m/Y');
                    ?>
                        <div class="violation-card" id="card-<?php echo $item['id']; ?>" onclick="openModalPHP('<?php echo $imgSrc; ?>', '<?php echo $nomeSafe; ?>', '<?php echo $epiSafe; ?>', '<?php echo $horaF; ?>', '<?php echo $dataF; ?>', '<?php echo $item['aluno_id']; ?>', '<?php echo $item['id']; ?>', <?php echo $item['is_assinada']; ?>)">
                            <?php if ($item['is_assinada']): ?>
                                <button class="btn-dismiss" title="Remover da vista" onclick="event.stopPropagation(); dismissOccurrence(<?php echo $item['id']; ?>)">
                                    <i data-lucide="x"></i>
                                </button>
                            <?php endif; ?>
                            <div class="card-image-wrapper">
                                <img src="<?php echo $imgSrc; ?>" class="card-image" loading="lazy">
                            </div>
                            <div class="card-content">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span class="violation-tag"><?php echo $epiSafe; ?></span>
                                    <?php if ($item['is_assinada']): ?>
                                        <span class="status-assinada">Assinado</span>
                                    <?php endif; ?>
                                </div>
                                <span class="infrator-name"><?php echo $nomeSafe; ?></span>
                                <div class="timestamp"><?php echo $horaF; ?> • <?php echo $setorSafe; ?></div>
                            </div>
                        </div>
                    <?php
                    endforeach; ?>
                <?php
                endif; ?>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="imageModal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button onclick="forceClose()" style="position:absolute; right:10px; top:10px; border:none; background:transparent; font-size:24px; cursor:pointer;">&times;</button>
            <img src="" id="modalImg" class="full-image">
            <div style="text-align:left; width:100%;">
                <h3 id="modalName" style="margin: 5px 0 0 0; color:#1f2937;">Nome</h3>
                <p id="modalDesc" style="color:#dc2626; font-weight:bold; margin: 5px 0;">Infração</p>
                <p id="modalTime" style="color:#666; font-size:14px; margin:0;">Horário</p>
            </div>
            <button id="btnAssinar" class="btn-assinar">Assinar Ocorrência</button>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../js/infraçoes.js"></script>
    <script src="../js/notifications.js" defer></script>

    <script>
        // lucide.createIcons() já é chamado no infraçoes.js
        window.addEventListener('load', () => {
            const container = document.getElementById('cardsContainer');
            if (container) {
                // Pequeno delay para garantir que a transição de página já começou
                setTimeout(() => {
                    container.classList.add('ready');
                }, 100);
            }
        });
    </script>
</body>

</html>