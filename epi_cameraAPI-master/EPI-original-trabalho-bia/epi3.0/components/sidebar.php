<?php
// Identifica a página atual para marcar a classe "active" no menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
    <aside class="sidebar">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E30613" stroke-width="3"
                style="filter: drop-shadow(0 2px 4px rgba(227, 6, 19, 0.3));">
                <circle cx="12" cy="12" r="10" />
            </svg>

            &nbsp; EPI <span>GUARD</span>
        </div>

        <nav class="nav-menu">

            <a class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>

             <a class="nav-item <?= ($current_page == 'monitoramento.php') ? 'active' : ''; ?>" href="monitoramento.php">
                <i data-lucide="monitor"></i>
                <span>Monitoramento</span>
            </a>

            <a class="nav-item <?= ($current_page == 'infracoes.php') ? 'active' : ''; ?>" href="infracoes.php">
                <i data-lucide="alert-triangle"></i>
                <span>Infrações</span>
            </a>

            <a class="nav-item <?= ($current_page == 'controleSala.php') ? 'active' : ''; ?>" href="controleSala.php">
                <i data-lucide="users"></i>
                <span>Controle de Sala</span>
            </a>

            <a class="nav-item <?= ($current_page == 'ocorrencias.php') ? 'active' : ''; ?>" href="ocorrencias.php">
                <i data-lucide="file-text"></i>
                <span>Ocorrências</span>
            </a>

            <a class="nav-item <?= ($current_page == 'configuracoes.php') ? 'active' : ''; ?>" href="configuracoes.php">
                <i data-lucide="settings"></i>
                <span>Configurações</span>
            </a>

        </nav>
        
        <?php //// INÍCIO CÓDIGO DO ASSISTENTE IA //// ?>
        <div class="ai-button-area" style="padding-top: 20px; margin-top: auto;">
            <button class="ai-trigger-btn" onclick="toggleAssistenteIA()" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; cursor: pointer; font-weight: 600; color: #1f2937; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <i data-lucide="sparkles" style="width: 18px; height: 18px; color: #1f2937;"></i>
                <span>Assistente IA</span>
            </button>
        </div>
        <?php //// FIM CÓDIGO DO ASSISTENTE IA //// ?>

    </aside>

    <?php 
    //// INÍCIO INCLUSÃO DO ASSISTENTE IA ////
    include __DIR__ . '/assistente_ia.php'; 
    //// FIM INCLUSÃO DO ASSISTENTE IA ////
    ?>