<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPI GUARD | Configurações</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../css/configuracoes.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="../css/transitions.css">
    <script src="../js/transitions.js"></script>
</head>

<body>

   <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <main class="main-content">
        <div id="view-config" class="view-section active">
            <header>
                <div class="page-title">
                    <h1>Configurações do Sistema</h1>
                    <p>Personalize a aparência e o comportamento da dashboard</p>
                </div>
            </header>

            <div class="config-grid">

                <div class="config-card">
                    <div class="config-header"><i data-lucide="monitor"></i> Interface</div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Modo Noturno</span>
                            <small>Alternar tema escuro/claro</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-darkmode" onchange="toggleTheme()">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Exibir Porcentagem</span>
                            <small>Mostrar % nos cards do topo</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked id="toggle-percent"
                                onchange="togglePercentDisplay(this)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Exibir Status (Badges)</span>
                            <small>Mostrar/Ocultar fundo colorido</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked id="toggle-status" onchange="toggleStatus()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="config-card">
                    <div class="config-header"><i data-lucide="pie-chart"></i> Gráfico de EPIs</div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Tipo de Gráfico</span>
                            <small>Altera visual do fieldset</small>
                        </div>
                        <select class="form-select" onchange="changeChartType(this.value)">
                            <option value="donut">Rosca</option>
                            <option value="bar">Barras</option>
                            <option value="line">Linha</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Cor do Destaque</span>
                            <small>Muda cor dos gráficos</small>
                        </div>
                        <input type="color" value="#E30613" onchange="changeChartColor(this.value)">
                    </div>
                </div>

                <div class="config-card">
                    <div class="config-header"><i data-lucide="mouse-pointer"></i> Interatividade</div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Link nos Cards de Infrações</span>
                            <small>clique nos cards de infração para ir para outras paginas</small>

                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-link" onchange="toggleLinkAbility()">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Link nos Cards</span>
                            <small>Permitir clique para detalhes</small>

                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-link" onchange="toggleLinkAbility()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <div class="config-card">
                    <div class="config-header"><i data-lucide="refresh-cw"></i> Atualização de Dados</div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Auto-Refresh</span>
                            <small>Permitir que as informações mude</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Intervalo</span>
                            <small>Frequência de busca</small>
                        </div>
                        <select class="form-select" style="width: 140px;">
                            <option>Tempo Real</option>
                            <option>30 Segundos</option>
                            <option>1 Minuto</option>
                            <option>5 Minutos</option>
                        </select>
                    </div>
                </div>

                <div class="config-card">
                    <div class="config-header"><i data-lucide="bell"></i> Alertas e Sons</div>

                    <div class="control-row">
                        <div class="control-label">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span>Alerta Sonoro</span>
                                <button class="btn-test-sound" onclick="testNotificationSound()" title="Testar Som" style="padding: 0; background: none; border: none; cursor: pointer; color: var(--senai-red); display: flex;">
                                    <i data-lucide="volume-2" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                            <small>Tocar bip ao detectar infração</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-sound" checked onchange="toggleSound(this)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="control-label">
                            <span>Notificação</span>
                            <small>Enviar e-mail se infração crítica</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <script src="../js/configuracao.js"></script>
    <script src="../js/Dark.js"></script>
    <script src="../js/global.js"></script>
    <script src="../js/notifications.js" defer></script>
    
</body>

</html>