<?php
// Correção solicitada: auth.php (caminho relativo assumindo que está na pasta /pages/)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// --- CÓDIGO NOVO: Busca de alunos (Filtrado por Curso) ---
$cursoId = $_SESSION['usuario_id_curso'] ?? 1;
$sql_alunos = "SELECT id, nome, curso_id, turno, foto_referencia 
               FROM alunos 
               WHERE curso_id = ? 
               ORDER BY nome ASC";
$stmt_alunos = mysqli_prepare($conn, $sql_alunos);
mysqli_stmt_bind_param($stmt_alunos, "i", $cursoId);
mysqli_stmt_execute($stmt_alunos);
$result_alunos = mysqli_stmt_get_result($stmt_alunos);
// ------------------------------------
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPI Guard | Nova Ocorrência</title>
    <link rel="stylesheet" href="../css/Ocorrencia.css">
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="../css/transitions.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../js/Dark.js"></script>
    <script src="../js/transitions.js"></script>


</head>

<body>

     <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <main class="main-content">

        <header class="header">
            <div class="page-title">
                <h1>Painel Geral</h1>
                <p>Laboratório B • Monitoramento em Tempo Real</p>
            </div>
            <div class="header-actions">
                <button class="btn-export" onclick="exportData()">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 20h14v-2H5v2zM19 9h-4V3H9v6H5l7 7 7-7z" />
                    </svg>
                    Exportar
                </button>

                <div class="user-profile-trigger" id="profileTrigger" onclick="toggleInstructorCard()">
                    <div class="user-info-mini">
                        <span class="user-name">João Silva</span>
                        <span class="user-role">Téc. Segurança</span>
                    </div>
                    <div class="user-avatar">JS</div>
                </div>
            </div>

            <div class="instructor-card" id="instructorCard">
                <div style="margin-bottom: 20px;">
                    <h3>João Silva</h3>
                    <p style="color: #64748B; font-size: 13px;">ID: 9821-BR</p>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cargo</span>
                    <span class="detail-value">Supervisor</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Turno</span>
                    <span class="detail-value">Manhã/Tarde</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color:var(--success)">Online</span>
                </div>
                <button class="btn-close-card" onclick="sair()">Sair</button>
            </div>
        </header>

        <form class="form-container" id="incidentForm">
            <input type="hidden" id="ocorrenciaId" name="ocorrencia_id">


            <div class="form-section-title">
                 Dados da Infração (Automático)
            </div>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Aluno Identificado</label>
                    <select class="form-select" id="studentNameInput" name="aluno_id" required >
                        <option value="" disabled selected>Selecione um aluno...</option>
                        <?php
// Verifica se retornou algum aluno
if ($result_alunos && mysqli_num_rows($result_alunos) > 0) {
    // Cria uma opção (option) para cada aluno encontrado
    while ($aluno = mysqli_fetch_assoc($result_alunos)) {
        echo '<option value="' . htmlspecialchars($aluno['id']) . '" ';
        echo 'data-curso="' . htmlspecialchars($aluno['curso_id'] ?? '') . '" ';
        echo 'data-turno="' . htmlspecialchars($aluno['turno'] ?? '') . '" ';
        echo 'data-foto="' . htmlspecialchars($aluno['foto_referencia'] ?? '') . '">';
        echo htmlspecialchars($aluno['nome']);
        echo '</option>';
    }
}
else {
    echo '<option value="" disabled>Nenhum aluno encontrado</option>';
}
?>

                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo Principal</label>
                    <input type="text" class="form-input" id="reasonInput" value="..." readonly
                        style="color: var(--primary); font-weight: 700; background: #FEF2F2; border-color: #FCA5A5;">
                </div>

                <div class="form-group">
                    <label class="form-label">Data e Hora</label>
                    <input type="text" class="form-input" id="dateTimeInput" readonly>
                </div>
            </div>

            <div class="form-section-title">
                 Ação Tomada
            </div>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Tipo de Registro / Advertência</label>
                    <select class="form-select" id="actionType" name="tipo">
                        <option value="obs" selected> Adicionar Observação (Padrão)</option>
                        <option value="adv_verbal"> Advertência Verbal</option>
                        <option value="adv_escrita"> Advertência Escrita</option>
                        <option value="suspensao"> Suspensão</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Observações Adicionais</label>
                    <textarea class="form-textarea" name="observacao" placeholder="Descreva detalhes sobre a ocorrência..."></textarea>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Evidências</label>

                    <div class="photos-container" id="photoGallery">
                        <!-- Imagens dinâmicas ou uploads aparecerão aqui -->
                        <input type="file" id="fileInput" hidden multiple accept="image/*">

                        <div class="btn-add-photo" onclick="document.getElementById('fileInput').click()">
                            <span>+</span>
                            <p>Adicionar</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancelar</button>
                <button type="submit" class="btn btn-submit">
                    Confirmar Ocorrência
                </button>
            </div>
        </form>

    </main>
    <script src="../js/ocorrencias.js" defer></script>
    <script src="../js/notifications.js" defer></script>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>