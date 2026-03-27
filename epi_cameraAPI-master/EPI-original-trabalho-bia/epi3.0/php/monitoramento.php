<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPI Guard | Monitoramento</title>
    <link rel="stylesheet" href="../css/Ocorrencia.css">
    <link rel="stylesheet" href="../css/monitoramento.css">
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="../css/dark.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../js/Dark.js"></script>
    <script src="../js/Dark.js"></script>

    <style>
        .meet-wrapper {
            background-color: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1d1d1f;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: calc(100vh - 120px);
            margin-top: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.6);
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .meet-header-info {
            height: 54px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            font-size: 14px;
            font-weight: 500;
            background: #ffffff;
            border-bottom: 1px solid #e5e5ea;
        }

        .meet-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #515154;
        }

        .meet-main {
            flex: 1;
            display: flex;
            padding: 16px;
            gap: 16px;
            overflow: hidden;
            background-color: #f5f5f7;
        }

        .meet-presentation {
            flex: 3;
            background: #ffffff;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        }

        .editor-header {
            height: 40px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            font-size: 13px;
            font-weight: 600;
            color: #86868b;
            border-bottom: 1px solid #f0f0f2;
        }

        .editor-content {
            flex: 1;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            overflow: hidden;
        }

        .editor-content img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .meet-right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 400px;
        }

        .chat-panel {
            flex: 1;
            background: #ffffff;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            padding: 24px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        }

        .chat-header {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-subtitle {
            font-size: 12px;
            color: #34c759;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chat-subtitle::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #34c759;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(52, 199, 89, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(52, 199, 89, 0);
            }
        }

        .meet-footer {
            height: 88px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 32px;
            background: #ffffff;
            border-top: 1px solid #e5e5ea;
        }

        .controls {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .btn-meet {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid #e5e5ea;
            background: #ffffff;
            color: #1d1d1f;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-meet:hover {
            background: #f5f5f7;
        }

        .btn-danger {
            background: #ff3b30 !important;
            color: white !important;
            border: none !important;
        }

        .btn-success {
            background: #34c759 !important;
            color: white !important;
            border: none !important;
        }

        .chat-logs {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
            overflow-y: auto;
        }

        .infraction-card {
            background: #ffffff;
            border-left: 4px solid #ff3b30;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .infraction-icon {
            color: #ff3b30;
            background: #ffe5e5;
            padding: 8px;
            border-radius: 10px;
        }

        .infraction-content {
            flex: 1;
        }

        .infraction-title {
            font-weight: 700;
            font-size: 13px;
            color: #1d1d1f;
        }

        .infraction-message {
            font-size: 12px;
            color: #515154;
        }

        .infraction-time {
            font-size: 11px;
            color: #ff3b30;
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E30613" stroke-width="3">
                <circle cx="12" cy="12" r="10" />
            </svg>
            EPI <span>GUARD</span>
        </div>

        <nav class="nav-menu">
            <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
            <a class="nav-item" href="infracoes.php"><i data-lucide="alert-triangle"></i><span>Infrações</span></a>
            <a class="nav-item" href="controleSala.php"><i data-lucide="users"></i><span>Controle de Sala</span></a>
            <a class="nav-item" href="ocorrencias.php"><i data-lucide="file-text"></i><span>Ocorrências</span></a>
            <a class="nav-item" href="configuracoes.php"><i data-lucide="settings"></i><span>Configurações</span></a>
            <a class="nav-item" href="monitoramento.php"><i data-lucide="monitor"></i><span>Monitoramento</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="page-title">
                <h1>Monitoramento de Laboratório</h1>
                <p>Câmera Ao Vivo</p>
            </div>
        </header>

        <div class="meet-wrapper" id="meetWrapper">
            <div class="meet-header-info">
                <div class="meet-user-info">
                    <i data-lucide="shield-check" style="color: #34c759; width: 18px;"></i>
                    Visualizando como: <strong>Administrador</strong>
                </div>
            </div>

            <div class="meet-main">
                <section class="meet-presentation">
                    <div class="editor-header">
                        Câmera Principal
                        <span id="ia-detecao" style="margin-left: auto; display: none; align-items: center; gap: 6px; font-weight: 500;">
                            <i data-lucide="scan-face" size="16"></i>
                            <span id="ia-nome-usuario">Aguardando...</span>
                        </span>
                    </div>
                    <div class="editor-content" style="position: relative;">
                        <div id="camera-off-text" style="display: flex; position: absolute; flex-direction: column; align-items: center; color: white;">
                            <i data-lucide="video-off" size="48" style="color: #ff3b30; margin-bottom: 10px;" id="status-icon"></i>
                            <h2 style="margin: 0; font-size: 24px;" id="status-title">Câmera Desligada</h2>
                            <p style="color: #86868b; font-size: 14px;" id="status-desc">Selecione uma IA abaixo para iniciar.</p>
                        </div>
                        <img id="camera-feed" src="" alt="Câmera Ao Vivo" style="opacity: 0;">
                    </div>
                </section>

                <aside class="meet-right-panel">
                    <div class="chat-panel">
                        <div class="chat-header">Infrações Recentes <i data-lucide="alert-triangle" size="18" style="color: #ff3b30;"></i></div>
                        <div class="chat-subtitle">Monitoramento IA Ativado</div>
                        <div class="chat-logs" id="notification-container"></div>
                    </div>
                </aside>
            </div>

            <footer class="meet-footer">
                <div class="controls">
                    <button class="btn-meet btn-success" id="btn-camera-epi" onclick="toggleCamera('epi')" title="IA de EPIs">
                        <i data-lucide="video" size="20"></i>
                    </button>
                    <button class="btn-meet" style="background: #007aff; color: white; border: none;" id="btn-camera-facial" onclick="toggleCamera('facial')" title="Reconhecimento Facial">
                        <i data-lucide="scan-face" size="20"></i>
                    </button>
                </div>
            </footer>
        </div>
    </main>

    <!-- Modal Cadastro Facial -->
    <div id="modal-cadastro" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; opacity: 0; transition: opacity 0.3s;">
        <div style="background: white; padding: 24px 32px; border-radius: 20px; width: 340px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); transform: scale(0.9); transition: transform 0.3s; box-sizing: border-box;" id="modal-content-box">
            <div style="background: rgba(227, 6, 19, 0.1); color: #E30613; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="user-plus" size="24"></i>
            </div>
            <h3 style="margin: 0 0 8px; font-size: 18px; color: #1d1d1f;">Novo Cadastro Facial</h3>
            <p style="font-size: 14px; color: #515154; margin-bottom: 24px;">Rosto desconhecido detectado. Deseja registrar no sistema?</p>
            <input type="number" id="cad-id" placeholder="ID / Matrícula" style="width: 100%; padding: 12px; margin-bottom: 12px; border: 1px solid #e5e5ea; border-radius: 12px; font-size: 14px; outline: none; box-sizing: border-box;">
            <input type="text" id="cad-nome" placeholder="Nome Completo" style="width: 100%; padding: 12px; margin-bottom: 24px; border: 1px solid #e5e5ea; border-radius: 12px; font-size: 14px; outline: none; box-sizing: border-box;">
            <button onclick="enviarCadastro()" style="background: #E30613; color: white; border: none; padding: 12px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: 600; font-size: 15px; margin-bottom: 8px; transition: background 0.2s;">Iniciar Captura</button>
            <button onclick="fecharModal()" style="background: #f5f5f7; color: #86868b; border: none; padding: 12px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: 500; font-size: 15px; transition: background 0.2s;">Pular</button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let cameraAtual = null; // 'epi' ou 'facial'
        let modalAberto = false;
        let ignorarCadastroAte = 0;

        async function toggleCamera(tipo) {
            const cameraFeed = document.getElementById('camera-feed');
            const statusBox = document.getElementById('camera-off-text');
            const statusTitle = document.getElementById('status-title');
            const statusDesc = document.getElementById('status-desc');
            const statusIcon = document.getElementById('status-icon');

            const urlEpi = 'http://localhost:5000';
            const urlFacial = 'http://localhost:5001';
            const urlDestino = tipo === 'epi' ? urlEpi : urlFacial;

            // 1. Se clicou no botão da IA que já está ligada -> Desliga tudo
            if (cameraAtual === tipo) {
                statusBox.style.display = "flex";
                statusTitle.innerText = "Desligando...";
                statusDesc.innerText = "Finalizando servidor Python...";
                cameraFeed.src = "";
                cameraFeed.style.opacity = "0";

                // Desliga a câmera no Flask e depois mata o processo Python
                await fetch(urlDestino + '/desligar').catch(() => {});
                await fetch('api_python.php?acao=parar&tipo=' + tipo).catch(() => {});

                cameraAtual = null;
                statusTitle.innerText = "Câmera Desligada";
                statusDesc.innerText = "Selecione uma IA abaixo para iniciar.";
                statusIcon.setAttribute('data-lucide', 'video-off');
                resetBotoes();
            } else {
                // 2. Se tinha a outra ligada, desliga antes (libera hardware)
                if (cameraAtual) {
                    const urlAntiga = cameraAtual === 'epi' ? urlEpi : urlFacial;
                    await fetch(urlAntiga + '/desligar').catch(() => {});
                    await fetch('api_python.php?acao=parar&tipo=' + cameraAtual).catch(() => {});
                    await new Promise(r => setTimeout(r, 1500));
                }

                // 3. Interface de Loading
                statusBox.style.display = "flex";
                statusTitle.innerText = "Iniciando IA...";
                statusDesc.innerText = `Iniciando servidor Python ${tipo.toUpperCase()}...`;
                statusIcon.setAttribute('data-lucide', 'loader');
                cameraFeed.style.opacity = "0";
                cameraFeed.src = "";
                resetBotoes();
                lucide.createIcons();

                // 4. Iniciar o processo Python via PHP
                try {
                    const resp = await fetch('api_python.php?acao=iniciar&tipo=' + tipo);
                    const data = await resp.json();
                    if (data.status === 'ja_rodando') {
                        statusDesc.innerText = `Servidor ${tipo.toUpperCase()} já estava ativo. Conectando...`;
                    } else {
                        statusDesc.innerText = `Processo Python iniciado. Carregando modelos de IA...`;
                    }
                } catch (e) {
                    statusTitle.innerText = "Erro";
                    statusDesc.innerText = "Falha ao comunicar com api_python.php. Verifique se o Apache está rodando.";
                    statusIcon.setAttribute('data-lucide', 'alert-circle');
                    lucide.createIcons();
                    return;
                }

                // 5. Aguardar o servidor Python inicializar (polling com timeout de 45s)
                let pronto = false;
                for (let i = 0; i < 45; i++) {
                    try {
                        const controller = new AbortController();
                        const timer = setTimeout(() => controller.abort(), 2000);
                        const res = await fetch(urlDestino + '/status_ia', { signal: controller.signal });
                        clearTimeout(timer);
                        if (res.ok) {
                            pronto = true;
                            break;
                        }
                    } catch (e) {}
                    statusDesc.innerText = `Aguardando servidor ${tipo.toUpperCase()}... (${i + 1}s)`;
                    await new Promise(r => setTimeout(r, 1000));
                }

                if (!pronto) {
                    statusTitle.innerText = "Erro de Conexão";
                    statusDesc.innerText = "Não foi possível conectar ao servidor Python. Tente novamente.";
                    statusIcon.setAttribute('data-lucide', 'alert-circle');
                    lucide.createIcons();
                    // Tenta matar o processo que pode ter travado
                    await fetch('api_python.php?acao=parar&tipo=' + tipo).catch(() => {});
                    return;
                }

                // 6. Ligar a câmera no servidor Python
                await fetch(urlDestino + '/ligar').catch(() => {});

                // Espera o OpenCV inicializar o driver da câmera
                statusDesc.innerText = "Inicializando câmera...";
                await new Promise(r => setTimeout(r, 2000));

                // 7. Ativar Feed
                cameraAtual = tipo;
                cameraFeed.src = urlDestino + "/video_feed?t=" + Date.now();
                cameraFeed.style.opacity = "1";
                statusBox.style.display = "none";

                // 8. Atualizar Estilo Botão
                const btnEpi = document.getElementById('btn-camera-epi');
                const btnFacial = document.getElementById('btn-camera-facial');
                if (tipo === 'epi') {
                    btnEpi.className = "btn-meet btn-danger";
                    btnEpi.querySelector('i, svg').setAttribute('data-lucide', 'video-off');
                } else {
                    btnFacial.style.background = "#ff3b30";
                    btnFacial.querySelector('i, svg').setAttribute('data-lucide', 'video-off');
                }
            }
            lucide.createIcons();
        }

        function resetBotoes() {
            const btnEpi = document.getElementById('btn-camera-epi');
            const btnFacial = document.getElementById('btn-camera-facial');

            btnEpi.className = "btn-meet btn-success";
            btnEpi.querySelector('i, svg').setAttribute('data-lucide', 'video');

            btnFacial.style.background = "#007aff";
            btnFacial.querySelector('i, svg').setAttribute('data-lucide', 'scan-face');
        }

        function verificarStatusIA() {
            if (!cameraAtual) return;

            const url = cameraAtual === 'epi' ? 'http://localhost:5000' : 'http://localhost:5001';

            fetch(url + '/status_ia')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('ia-detecao');
                    const texto = document.getElementById('ia-nome-usuario');
                    container.style.display = 'flex';

                    if (data.nome === "Desconhecido") {
                        texto.innerHTML = "Rosto Desconhecido";
                        container.style.color = "#ff3b30";

                        // Trigger Modal Cadastro (Apenas no modo Facial)
                        if (cameraAtual === 'facial' && !modalAberto && !data.cadastro_em_andamento) {
                            if (Date.now() > ignorarCadastroAte) {
                                console.log("Disparando Modal de Cadastro...");
                                abrirModal();
                            }
                        }
                    } else if (data.nome.startsWith("Cadastrando")) {
                        texto.innerText = data.nome;
                        container.style.color = "#ff9500";
                    } else {
                        texto.innerText = data.nome;
                        container.style.color = "#34c759";
                    }
                })
                .catch(() => {});
        }

        function abrirModal() {
            modalAberto = true;
            const modal = document.getElementById('modal-cadastro');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.opacity = '1';
                document.getElementById('modal-content-box').style.transform = 'scale(1)';
            }, 50);
        }

        function fecharModal() {
            const modal = document.getElementById('modal-cadastro');
            modal.style.opacity = '0';
            document.getElementById('modal-content-box').style.transform = 'scale(0.9)';
            ignorarCadastroAte = Date.now() + 15000;
            setTimeout(() => {
                modal.style.display = 'none';
                modalAberto = false;
            }, 300);
        }

        function enviarCadastro() {
            const id = document.getElementById('cad-id').value;
            const nome = document.getElementById('cad-nome').value;

            if (!id || !nome) return alert("Preencha todos os campos!");

            fetch('http://localhost:5001/solicitar_cadastro', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: parseInt(id),
                    nome: nome
                })
            }).then(() => {
                fecharModal();
                ignorarCadastroAte = Date.now() + 60000;
            });
        }

        setInterval(verificarStatusIA, 300);
    </script>
</body>

</html>