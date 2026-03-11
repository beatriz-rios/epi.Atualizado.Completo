// Verifica se o JS carregou
console.log("Infracoes.js carregado com sucesso.");

document.addEventListener("DOMContentLoaded", () => {
    lucide.createIcons();

    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.violation-card');
    const container = document.getElementById('cardsContainer');

    // --- Lógica de Pesquisa Funcional ---
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let hasResults = false;

            cards.forEach(card => {
                const studentName = card.querySelector('.infrator-name').textContent.toLowerCase();
                const epiName = card.querySelector('.violation-tag').textContent.toLowerCase();
                const details = card.querySelector('.timestamp').textContent.toLowerCase();

                const matches = studentName.includes(searchTerm) ||
                    epiName.includes(searchTerm) ||
                    details.includes(searchTerm);

                if (matches) {
                    card.classList.remove('hidden-search');
                    card.style.display = 'flex';
                    hasResults = true;
                } else {
                    card.classList.add('hidden-search');
                    setTimeout(() => {
                        if (card.classList.contains('hidden-search')) {
                            card.style.display = 'none';
                        }
                    }, 400);
                }
            });

            // Gerenciar mensagem de "Nenhum resultado"
            let noResultsMsg = document.getElementById('noResultsMsg');
            if (!hasResults && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('p');
                    noResultsMsg.id = 'noResultsMsg';
                    noResultsMsg.style.cssText = 'padding: 40px; color: #64748B; text-align: center; width: 100%; font-weight: 500; animation: fadeIn 0.5s ease;';
                    noResultsMsg.textContent = '🔍 Nenhum aluno ou infração encontrada para "' + searchTerm + '"';
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        });
    }

    // --- Links de Navegação Sidebar ---
    const links = document.querySelectorAll('a.nav-item');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript:')) return;
            e.preventDefault();
            document.body.classList.add('page-exit');
            setTimeout(() => { window.location.href = href; }, 300);
        });
    });

    // --- Verificar parâmetros da URL para busca automática ---
    const urlParams = new URLSearchParams(window.location.search);
    const autoBusca = urlParams.get('busca');
    if (autoBusca && searchInput) {
        searchInput.value = autoBusca;
        // Dispara o evento de input para filtrar os cards
        searchInput.dispatchEvent(new Event('input'));
    }
});

// --- FUNÇÃO DO MODAL (Chamada pelo PHP) ---
function openModalPHP(imgUrl, nome, epi, horaTexto, dataCompleta, alunoId, ocorrenciaId) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImg');
    const modalName = document.getElementById('modalName');
    const modalDesc = document.getElementById('modalDesc');
    const modalTime = document.getElementById('modalTime');
    const btnAssinar = document.getElementById('btnAssinar');

    if (!modal) return;

    // 1. Preenche os dados visuais
    if (modalImg) modalImg.src = imgUrl;
    if (modalName) modalName.innerText = nome;
    if (modalDesc) modalDesc.innerText = "Infração: " + epi;
    if (modalTime) modalTime.innerText = "Horário: " + horaTexto + " | Data: " + dataCompleta;

    // 2. Configura o botão de assinar
    if (btnAssinar) {
        btnAssinar.onclick = function () {
            const params = new URLSearchParams({
                ocorrencia_id: ocorrenciaId,
                aluno_id: alunoId,
                epi: epi,
                data: dataCompleta,
                hora: horaTexto
            });
            window.location.href = `ocorrencias.php?${params.toString()}`;
        };
    }

    // 3. Mostra o modal
    modal.classList.add('active');
}

function closeModal(event) {
    if (event.target.id === 'imageModal') {
        forceClose();
    }
}

function forceClose() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.classList.remove('active');
        document.getElementById('modalImg').src = "";
    }
}