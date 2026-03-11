// Inicializa os ícones do Lucide
        lucide.createIcons();

        // Controle do Dropdown de Layout
        function toggleLayoutMenu() {
            const dropdown = document.getElementById('layoutDropdown');
            dropdown.classList.toggle('active');
        }

        // Função para alterar o Layout (Padrão vs Expandido)
        function setLayout(mode) {
            const wrapper = document.getElementById('meetWrapper');
            const optDefault = document.getElementById('opt-default');
            const optExpanded = document.getElementById('opt-expanded');

            if (mode === 'expanded') {
                wrapper.classList.add('layout-expanded');
                optExpanded.classList.add('selected');
                optDefault.classList.remove('selected');
            } else {
                wrapper.classList.remove('layout-expanded');
                optDefault.classList.add('selected');
                optExpanded.classList.remove('selected');
            }

            // Fecha o menu após clicar
            document.getElementById('layoutDropdown').classList.remove('active');
        }

        // Fecha o dropdown se clicar fora dele
        window.addEventListener('click', function(e) {
            const container = document.querySelector('.layout-menu-container');
            if (container && !container.contains(e.target)) {
                document.getElementById('layoutDropdown').classList.remove('active');
            }
        });
        // <------------------------------------------>
        // LÓGICA DE NOTIFICAÇÕES (BANCO DE DADOS)
        // <------------------------------------------>
        let ultimoIdNotificacao = 0;

        function mostrarNotificacao(aluno, epi_nome, hora_banco) {
            const container = document.getElementById('notification-container');
            const card = document.createElement('div');
            card.className = 'infraction-card';

            // Tratamento da hora (caso o banco não envie, pega a hora atual do PC)
            let horaExibicao = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
            if (hora_banco) {
                // Tenta formatar a data_hora vinda do banco (ex: "2023-10-25 14:30:00")
                const dataObj = new Date(hora_banco);
                if (!isNaN(dataObj.getTime())) {
                    horaExibicao = dataObj.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }

            // Constrói o HTML do card
            card.innerHTML = `
                <div class="infraction-icon">
                    <i data-lucide="alert-circle" width="20" height="20"></i>
                </div>
                <div class="infraction-content">
                    <div class="infraction-title">Alerta de EPI</div>
                    <div class="infraction-message"><b>${aluno}</b> • ${epi_nome}</div>
                    <span class="infraction-time">${horaExibicao}</span>
                </div>
            `;

            // Usa prepend para colocar a notificação mais recente no TOPO da lista
            container.prepend(card);

            // Renderiza o ícone do lucide no card recém-criado
            lucide.createIcons({
                root: card
            });
        }

        function verificarNovasOcorrencias() {
            // Adicionado as crases (`) em volta da URL
            fetch(`../php/check_notificacoes.php?last_id=${ultimoIdNotificacao}`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
            }
                })
                .then(res => res.json())
                .then(data => {
                    console.log("RETORNO COMPLETO:", data);

                    if (data.status === 'init') {
                        ultimoIdNotificacao = data.last_id;
                        return;
                    }

                    if (data.status === 'success' && data.dados.length > 0) {
                        data.dados.forEach(ocorrencia => {
                            mostrarNotificacao(
                                ocorrencia.aluno,
                                ocorrencia.epi_nome,
                                ocorrencia.data_hora
                            );
                            // Atualiza o último ID processado
                            ultimoIdNotificacao = ocorrencia.id;
                        });
                    }
                })
                .catch(err => console.error("Erro na verificação de ocorrências:", err));
        }

        // Executa a cada 5 segundos
        setInterval(verificarNovasOcorrencias, 5000);



        // Função para Ligar/Desligar a Câmera
        function toggleCamera() {
            const cameraFeed = document.getElementById('camera-feed');
            const btnCamera = document.getElementById('btn-camera');
            const icone = btnCamera.querySelector('i');
            const textOff = document.getElementById('camera-off-text');

            if (cameraFeed.src.includes('video_feed')) {
                // DESLIGAR (Fica Verde com ícone de Câmera normal)
                cameraFeed.src = ""; // Remove o link, cortando a conexão com o Python
                cameraFeed.style.opacity = "0"; // Esconde a imagem
                textOff.style.display = "flex"; // Mostra o texto de câmera desligada

                btnCamera.style.background = "#34c759"; // Botão fica verde
                icone.setAttribute('data-lucide', 'video'); // Ícone de ligar a câmera
            } else {
                // LIGAR (Fica Vermelho com ícone de Câmera cortada)
                // O "?t=" evita que o navegador pegue a imagem em cache
                cameraFeed.src = "http://localhost:5000/video_feed?t=" + new Date().getTime();
                cameraFeed.style.opacity = "1"; // Mostra a imagem
                textOff.style.display = "none"; // Esconde o texto

                btnCamera.style.background = "#ff3b30"; // Botão volta a ficar vermelho
                icone.setAttribute('data-lucide', 'video-off'); // Ícone de desligar a câmera
            }

            // Atualiza os ícones na tela
            lucide.createIcons({
                root: btnCamera
            });
            lucide.createIcons({
                root: textOff
            });
        }