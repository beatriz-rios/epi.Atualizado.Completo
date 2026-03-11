// Funções do Header que faltavam
function toggleInstructorCard() {
    const card = document.getElementById('instructorCard');
    card.classList.toggle('active');
}

function exportData() {
    alert("Exportando dados...");
}

document.addEventListener("DOMContentLoaded", () => {
    // --- 1. POPULAR DADOS (Simulando o que vem do Dashboard) ---
    const urlParams = new URLSearchParams(window.location.search);

    const alunoId = urlParams.get('aluno_id');
    const epiMissing = urlParams.get('epi');
    const ocorrenciaId = urlParams.get('ocorrencia_id');
    const isNovoManual = urlParams.get('novo') === 'true';

    const gallery = document.getElementById('photoGallery');

    // Preencher Ocorrencia ID (hidden)
    if (ocorrenciaId) {
        document.getElementById('ocorrenciaId').value = ocorrenciaId;

        // Carregar Foto Real da IA
        const wrapper = document.createElement('div');
        wrapper.className = 'photo-wrapper';
        wrapper.innerHTML = `
            <img src="mostrar_imagem.php?id=${ocorrenciaId}" class="photo-preview" alt="Infração Real">
            <div class="photo-badge">Detecção IA</div>
        `;
        gallery.insertBefore(wrapper, gallery.firstChild);
    } else if (isNovoManual) {
        // Se for Manual, não exibe imagem fake a pedido do usuário
    }

    // Preencher Aluno por ID (se vier da URL)
    if (alunoId) {
        const select = document.getElementById('studentNameInput');
        if (select) {
            select.value = alunoId;
        }
    }

    // Preencher Motivo se vier da URL
    if (epiMissing) {
        document.getElementById('reasonInput').value = `Ausência de EPI: ${epiMissing}`;
    }

    // Preencher Data/Hora Formatada
    const pData = urlParams.get('data');
    const pHora = urlParams.get('hora');

    if (pData && pHora) {
        document.getElementById('dateTimeInput').value = `${pData} às ${pHora}`;
    } else {
        const now = new Date();
        const formatted = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR').substring(0, 5);
        document.getElementById('dateTimeInput').value = formatted;
    }
});

// --- 2. LÓGICA DE FOTOS ADICIONAIS ---
const fileInput = document.getElementById('fileInput');
const gallery = document.getElementById('photoGallery');

if (fileInput) {
    fileInput.addEventListener('change', function () {
        if (this.files) {
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const div = document.createElement('div');
                    div.className = 'photo-wrapper-new';

                    const img = document.createElement('img');
                    img.src = e.target.result;

                    div.appendChild(img);

                    // Inserir antes do botão "+"
                    const addBtn = gallery.lastElementChild;
                    gallery.insertBefore(div, addBtn);
                }
                reader.readAsDataURL(file);
            });
        }
    });
}

// --- 3. ENVIO REAL ---
document.getElementById('incidentForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('.btn-submit');
    const originalText = btn.innerHTML;

    btn.innerHTML = 'Salvando...';
    btn.disabled = true;

    const formData = new FormData(this);

    fetch('../apis/api.php?action=resolve_occurrence', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert('Ocorrência registrada e confirmada com sucesso!');
                window.location.href = 'dashboard.php';
            } else {
                alert('Erro: ' + (response.error || 'Erro ao salvar.'));
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro na conexão com o servidor.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
});


function sair() {
    window.location.href = "index.php";
}
