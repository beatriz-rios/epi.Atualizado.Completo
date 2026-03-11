// ==========================================
// 1. VARIÁVEIS GLOBAIS E SELETORES
// ==========================================
let students = [];
const listContainer = document.getElementById('studentList');
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const modal = document.getElementById('detailModal');

// ==========================================
// 2. BUSCA DE DADOS (API)
// ==========================================
async function fetchStudents() {
    listContainer.innerHTML = '<div style="padding:20px; text-align:center;">🔄 Conectando ao sistema...</div>';

    // CAMINHO RELATIVO AUTOMÁTICO
    const url = '../apis/controle.api.php';

    try {
        const response = await fetch(url);

        if (response.status === 404) {
            throw new Error(`Arquivo API não encontrado.`);
        }

        const text = await response.text();
        try {
            const data = JSON.parse(text);

            if (data.error) {
                listContainer.innerHTML = `<div style="color:red; padding:20px; text-align:center">Erro do Banco: ${data.error}</div>`;
                return;
            }

            students = data;
            renderList();

        } catch (jsonError) {
            console.error("Erro ao ler JSON:", text);
            listContainer.innerHTML = `<div style="color:red; padding:20px;">Erro no PHP (veja o console F12).</div>`;
        }

    } catch (error) {
        console.error('Erro Fatal:', error);
        listContainer.innerHTML = `<div style="color:red; padding:20px; text-align:center;">❌ ${error.message}</div>`;
    }
}

// ==========================================
// 3. LÓGICA DE RENDERIZAÇÃO DA LISTA
// ==========================================

function getStudentState(student) {
    const hasRisk = student.missing && student.missing.length > 0;
    const hasHistory = student.history;

    if (hasRisk) return 'Risk';
    if (hasHistory) return 'History';
    return 'Safe';
}

function renderList(filterText = '', filterStatus = 'all') {
    listContainer.style.display = "grid";
    listContainer.style.gridTemplateColumns = "repeat(auto-fill, minmax(200px, 240px))";
    listContainer.style.gap = "12px";
    listContainer.style.justifyContent = "start";
    listContainer.innerHTML = '';

    const filtered = students.filter(s => {
        const state = getStudentState(s);
        const matchesText = s.name.toLowerCase().includes(filterText.toLowerCase());

        let matchesStatus = false;
        if (filterStatus === 'all') matchesStatus = true;
        else if (filterStatus === 'Risk' && state === 'Risk') matchesStatus = true;
        else if (filterStatus === 'History' && state === 'History') matchesStatus = true;
        else if (filterStatus === 'Safe' && state === 'Safe') matchesStatus = true;

        return matchesText && matchesStatus;
    });

    if (filtered.length === 0) {
        listContainer.style.display = "flex";
        listContainer.style.justifyContent = "center";
        listContainer.innerHTML = `
            <div style="text-align:center; padding: 40px; color: #94a3b8; animation: fadeIn 0.5s;">
                <p style="font-size: 14px;">Nenhum aluno encontrado.</p>
            </div>`;
        return;
    }

    filtered.forEach((student, index) => {
        const state = getStudentState(student);
        const initials = student.name.substring(0, 2).toUpperCase();

        let borderColor = 'transparent';
        let badgeBg = '#F3F4F6';
        let badgeColor = '#6B7280';
        let icon = '';

        if (state === 'Risk') {
            borderColor = '#EF4444';
            badgeBg = '#FEF2F2';
            badgeColor = '#EF4444';
            icon = '⚠️';
        } else if (state === 'History') {
            borderColor = '#F59E0B';
            badgeBg = '#FFFBEB';
            badgeColor = '#D97706';
            icon = '🔔';
        }

        const card = document.createElement('div');

        card.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            border: 1px solid #E2E8F0;
            border-left: 4px solid ${state === 'Safe' ? '#10B981' : borderColor};
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            opacity: 0;
            transform: scale(0.9);
            animation-delay: ${index * 0.05}s;
        `;

        card.onmouseenter = () => {
            card.style.transform = "translateY(-2px) scale(1.02)";
            card.style.boxShadow = "0 8px 16px -4px rgba(0,0,0,0.1)";
        };
        card.onmouseleave = () => {
            card.style.transform = "translateY(0) scale(1)";
            card.style.boxShadow = "0 2px 4px rgba(0,0,0,0.02)";
        };

        card.onclick = () => openModal(student);

        card.innerHTML = `
            <div style="
                width: 38px; height: 38px; 
                background: #F8FAFC; 
                border-radius: 50%; 
                display: flex; align-items: center; justify-content: center;
                font-size: 13px; font-weight: 700; color: #475569;
                border: 1px solid #E2E8F0; flex-shrink: 0;">
                ${initials}
            </div>
            
            <div style="flex: 1; min-width: 0;">
                <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: #1E293B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${student.name}
                </h3>
                <p style="margin: 2px 0 0 0; font-size: 11px; color: #94A3B8;">
                    ${student.course}
                </p>
            </div>

            ${state !== 'Safe' ? `
            <div style="
                font-size: 10px; font-weight: 700; 
                color: ${badgeColor}; background: ${badgeBg};
                padding: 4px 8px; border-radius: 6px;">
                ${icon}
            </div>` : ''}
        `;

        listContainer.appendChild(card);
    });

    if (!document.getElementById('anim-style')) {
        const style = document.createElement('style');
        style.id = 'anim-style';
        style.innerHTML = `
            @keyframes popIn {
                0% { opacity: 0; transform: scale(0.8) translateY(10px); }
                100% { opacity: 1; transform: scale(1) translateY(0); }
            }
        `;
        document.head.appendChild(style);
    }
}

// ==========================================
// 4. LÓGICA DO MODAL
// ==========================================

function openModal(student) {
    const modalElement = document.getElementById('detailModal');
    if (!modalElement) {
        console.error("Modal 'detailModal' não encontrado!");
        return;
    }

    const missingEpis = Array.isArray(student.missing) ? student.missing : [];
    const state = getStudentState(student);

    const nomeEl = document.getElementById('modalName');
    const cursoEl = document.getElementById('modalCourse');

    if (nomeEl) nomeEl.innerText = student.name;
    if (cursoEl) cursoEl.innerText = `${student.course} • ID #${student.id}`;

    const epiContainer = document.getElementById('modalEpiList');
    if (epiContainer) {
        epiContainer.innerHTML = '';
        const checkListEpis = ["Capacete", "Óculos"];

        checkListEpis.forEach(epi => {
            const isMissing = missingEpis.some(m =>
                typeof m === 'string' && m.toLowerCase().includes(epi.toLowerCase())
            );

            const item = document.createElement('div');
            item.style.cssText = "display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eee;";

            if (isMissing) {
                item.innerHTML = `<span style="font-weight:bold; color:#444">${epi}</span> <span style="color:red; font-weight:bold">❌ Ausente</span>`;
            } else {
                item.innerHTML = `<span style="color:#666">${epi}</span> <span style="color:green">✅ Ok</span>`;
            }
            epiContainer.appendChild(item);
        });
    }

    const footer = document.getElementById('modalFooterActions');
    if (footer) {
        const nomeSeguro = (student.name || '').replace(/'/g, "\\'");
        const episSafe = missingEpis.join(', ').replace(/'/g, "\\'");

        // Bloqueio: Só permite abrir ocorrência se o estado NÃO for "Safe"
        const isSafe = state === 'Safe';

        footer.innerHTML = `
            <button class="btn-view-infracoes" onclick="irParaInfracoes('${nomeSeguro}')">
                <i data-lucide="search"></i> Ver Infrações
            </button>
            <button class="btn-open-occurrence" 
                ${isSafe ? 'disabled style="opacity: 0.5; cursor: not-allowed; background: #94a3b8; border-color: #94a3b8;"' : ''} 
                onclick="abrirOcorrencia(${student.id}, '${nomeSeguro}', '${episSafe}')">
                <i data-lucide="plus-circle"></i> Abrir Ocorrência
            </button>
        `;

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Mostra o modal - usando ambas as classes para garantir compatibilidade com o CSS
    modalElement.style.display = 'flex';
    modalElement.classList.add('active');
    modalElement.classList.add('open');
}

// Alias para compatibilidade com versões anteriores que usavam esse nome
window.exibirDetalhesAluno = openModal;

function closeModal() {
    const modalElement = document.getElementById('detailModal');
    if (modalElement) {
        modalElement.style.display = 'none';
        modalElement.classList.remove('active');
        modalElement.classList.remove('open');
    }
}

// Funções de Navegação
function irParaInfracoes(nomeAluno) {
    if (!nomeAluno) return;
    const nomeCodificado = encodeURIComponent(nomeAluno);
    const url = `infracoes.php?periodo=hoje&busca=${nomeCodificado}`;

    if (window.navigateTo) {
        window.navigateTo(url);
    } else {
        window.location.href = url;
    }
}

function abrirOcorrencia(id, nome, epis) {
    if (!id) return;
    const params = new URLSearchParams({
        novo: 'true',
        aluno_id: id,
        name: nome,
        epi: epis
    });
    const url = `ocorrencias.php?${params.toString()}`;

    if (window.navigateTo) {
        window.navigateTo(url);
    } else {
        window.location.href = url;
    }
}

window.onclick = function (event) {
    if (event.target == modal) {
        closeModal();
    }
}

// ==========================================
// 5. INICIALIZAÇÃO E EVENTOS
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    fetchStudents();

    if (searchInput) {
        searchInput.addEventListener('keyup', (e) => renderList(e.target.value, statusFilter.value));
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', (e) => renderList(searchInput.value, e.target.value));
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function toggleInstructorCard() {
    const card = document.getElementById('instructorCard');
    if (card) {
        card.style.display = (card.style.display === 'block') ? 'none' : 'block';
    }
}