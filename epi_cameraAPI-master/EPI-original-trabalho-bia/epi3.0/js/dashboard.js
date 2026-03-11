// =============================================================
// DASHBOARD.JS - VERSÃO UNIFICADA (SUA LÓGICA + CALENDÁRIO VISUAL)
// =============================================================

// --- VARIÁVEIS GLOBAIS ---
let selectedDate = new Date(); // Data usada no Dashboard
let currCalYear = new Date().getFullYear(); // Ano visualizado no Modal de Escolha de Data
let currCalMonth = new Date().getMonth();   // Mês visualizado no Modal de Escolha de Data
let allOccurrences = []; // Dados do BD
let mainChartInstance = null;
let doughnutChartInstance = null;

// Arrays auxiliares
const monthsFull = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

// --- INICIALIZAÇÃO ---
document.addEventListener("DOMContentLoaded", function () {
    loadCalendarData(); // Carrega dados da API
    loadCharts();       // Carrega Gráficos

    // Listeners do Modal de ESCOLHA DE DATA (Calendário Visual)
    const btnPrev = document.getElementById('prevMonth');
    const btnNext = document.getElementById('nextMonth');
    if (btnPrev) btnPrev.addEventListener('click', () => changeCalMonth(-1));
    if (btnNext) btnNext.addEventListener('click', () => changeCalMonth(1));

    // Input Manual
    const input = document.getElementById('manualDateInput');
    if (input) {
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') commitManualDate(); });
        input.addEventListener('input', maskDateInput);
    }

    // Fechar modais ao clicar fora
    document.addEventListener('click', (e) => {
        // Modal Calendário
        const calModal = document.getElementById('calendarModal');
        if (calModal && e.target === calModal) toggleCalendar();

        // Modal Detalhes (Gráfico)
        const detModal = document.getElementById('detailModal');
        if (detModal && e.target === detModal) detModal.classList.remove('open');

        // Card Instrutor
        const card = document.getElementById('instructorCard');
        const trigger = document.getElementById('profileTrigger');
        if (card && trigger && !card.contains(e.target) && !trigger.contains(e.target)) {
            card.classList.remove('active');
        }
    });
});

// ===============================
// 1. LÓGICA DE DADOS (API & UPDATE)
// ===============================

function loadCalendarData() {
    // Pega o mês e ano da data SELECIONADA
    const month = selectedDate.getMonth() + 1;
    const year = selectedDate.getFullYear();

    fetch(`../apis/api.php?action=calendar&month=${month}&year=${year}`)
        .then(res => res.json())
        .then(data => {
            allOccurrences = Array.isArray(data) ? data : [];
            renderInterface(); // Atualiza tela
        })
        .catch(err => {
            console.error('Erro calendário:', err);
            allOccurrences = [];
            renderInterface();
        });
}

// Renderiza a interface principal (Lista lateral, Texto data, KPIs)
// =============================================================
// SUBSTITUA A FUNÇÃO renderInterface POR ESTA VERSÃO CORRIGIDA:
// =============================================================

function renderInterface() {
    // 1. Atualiza Texto da Data no Navegador
    const day = String(selectedDate.getDate()).padStart(2, '0');

    // PEGANDO O MÊS COMPLETO:
    const monthFullStr = monthsFull[selectedDate.getMonth()];
    const yearStr = selectedDate.getFullYear();

    const elNum = document.getElementById('displayDayNum');
    const elStr = document.getElementById('displayMonthStr');

    if (elNum) elNum.innerText = day;

    // AQUI ESTÁ O PULO DO GATO:
    // Injetamos o nome completo (ex: Fevereiro) + o Ano
    if (elStr) elStr.innerText = `${monthFullStr} ${yearStr}`;

    // 2. Filtra Ocorrências para a LISTA LATERAL
    const list = document.getElementById('occurrenceList');
    if (list) {
        list.innerHTML = '';
        const dailyData = allOccurrences.filter(item => {
            const dbDateString = item.full_date || item.data_hora || item.date;
            const itemDate = new Date(dbDateString.replace(/-/g, '/'));
            return isSameDay(selectedDate, itemDate);
        });

        if (dailyData.length > 0) {
            dailyData.forEach(item => {
                const initials = item.name ? item.name.substring(0, 2).toUpperCase() : '??';
                const safeName = (item.name || '').replace(/'/g, "\\'");
                list.innerHTML += `
                    <div class="occurrence-item" onclick="irParaInfracoes('${safeName}')" style="cursor: pointer;" title="Ver todas as infrações de ${item.name}">
                        <div class="occ-avatar">${initials}</div>
                        <div class="occ-info">
                            <span class="occ-name">${item.name}</span>
                            <span class="occ-desc">${item.desc}</span>
                        </div>
                        <div class="occ-time">${item.time}</div>
                    </div>`;
            });
        } else {
            list.innerHTML = `<div class="empty-state" style="padding:20px; text-align:center; color:#94a3b8; font-size:13px;">✅ Nenhuma infração neste dia.</div>`;
        }
    }

    // 3. Atualiza os KPIs
    updateKPICards();
    updatePercentagesDinamicamente();

    // 4. Aplica visibilidade de porcentagem e status (definida em global.js)
    if (typeof applyGlobalSettings === 'function') {
        applyGlobalSettings();
    }
}

// Navegação para infrações filtrada por nome
function irParaInfracoes(nome) {
    if (!nome) return;
    const url = `infracoes.php?periodo=todos&busca=${encodeURIComponent(nome)}`;
    window.location.href = url;
}

// Botões Setas do Dashboard (❮ ❯)
function changeDay(delta) {
    const oldMonth = selectedDate.getMonth();
    selectedDate.setDate(selectedDate.getDate() + delta);
    const newMonth = selectedDate.getMonth();

    if (oldMonth !== newMonth) {
        loadCalendarData(); // Mudou mês? Chama PHP
    } else {
        renderInterface(); // Mesmo mês? Só filtra JS
    }
}

// ===============================
// 2. HELPERS (DATA & KPI)
// ===============================

function isSameDay(d1, d2) {
    return d1.getFullYear() === d2.getFullYear() &&
        d1.getMonth() === d2.getMonth() &&
        d1.getDate() === d2.getDate();
}

function isSameWeek(d1, d2) {
    const date1 = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
    const date2 = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());

    // Ajusta para o início da semana (domingo)
    const start1 = new Date(date1);
    start1.setDate(date1.getDate() - date1.getDay());

    const start2 = new Date(date2);
    start2.setDate(date2.getDate() - date2.getDay());

    return start1.getTime() === start2.getTime();
}

function updateKPICards() {
    let countDay = 0, countWeek = 0, countMonth = 0;
    const selMonth = selectedDate.getMonth();
    const selYear = selectedDate.getFullYear();

    // Conjunto para rastrear alunos ÚNICOS com infração no dia selecionado
    const uniqueStudentsToday = new Set();

    allOccurrences.forEach(item => {
        const dbDateString = item.full_date || item.data_hora || item.date;
        const itemDate = new Date(dbDateString.replace(/-/g, '/'));

        if (isSameDay(selectedDate, itemDate)) {
            countDay++;
            if (item.aluno_id || item.student_id) {
                uniqueStudentsToday.add(item.aluno_id || item.student_id);
            }
        }
        if (isSameWeek(selectedDate, itemDate)) countWeek++;
        if (itemDate.getMonth() === selMonth && itemDate.getFullYear() === selYear) countMonth++;
    });

    const elDia = document.getElementById('kpiDia');
    const elSemana = document.getElementById('kpiSemana');
    const elMes = document.getElementById('kpiMes');
    const elMedia = document.getElementById('kpiMedia');

    if (elDia) elDia.innerText = countDay;
    if (elSemana) elSemana.innerText = countWeek;
    if (elMes) elMes.innerText = countMonth;

    // Cálculo da Conformidade: (Total - Alunos com Infração) / Total
    if (elMedia) {
        const total = window.totalStudents || 20; // fallback para 20 se não definido
        const infra = uniqueStudentsToday.size;
        const conformidade = Math.round(((total - infra) / total) * 100);
        elMedia.innerText = `${Math.max(0, conformidade)}%`;

        // Opcional: Atualizar o badge de status (🚨 CRÍTICO, etc) se necessário
        updateConformityStatus(conformidade);
    }
}

function updateConformityStatus(valor) {
    const card = document.getElementById('kpiMedia')?.parentElement;
    if (!card) return;

    let badge = card.querySelector('.status-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'status-badge';
        card.appendChild(badge);
    }

    if (valor < 70) {
        badge.className = 'status-badge status-critico';
        badge.innerText = '🚨 CRÍTICO';
    } else if (valor < 85) {
        badge.className = 'status-badge status-alto';
        badge.innerText = '🟠 ALTO RISCO';
    } else if (valor < 95) {
        badge.className = 'status-badge status-moderado';
        badge.innerText = '🟡 MODERADO';
    } else {
        badge.className = 'status-badge status-baixo';
        badge.innerText = '🟢 CONTROLADO';
    }
}

// ===============================
// 3. MODAL VISUAL (SELETOR DE DATA)
// ===============================

function toggleCalendar() {
    const modal = document.getElementById('calendarModal');
    if (!modal) return; // Segurança se não existir no HTML

    if (!modal.classList.contains('active')) {
        // Sincroniza visualização com a data atual selecionada
        currCalYear = selectedDate.getFullYear();
        currCalMonth = selectedDate.getMonth();
        renderCalendarGrid();
        modal.classList.add('active');
    } else {
        modal.classList.remove('active');
    }
}

function renderCalendarGrid() {
    const daysTag = document.getElementById("calendarDays");
    const monthTxt = document.getElementById("calMonthDisplay");
    const yearTxt = document.getElementById("calYearDisplay");

    if (!daysTag) return;

    let firstDayofMonth = new Date(currCalYear, currCalMonth, 1).getDay();
    let lastDateofMonth = new Date(currCalYear, currCalMonth + 1, 0).getDate();
    let lastDayofMonthIndex = new Date(currCalYear, currCalMonth, lastDateofMonth).getDay();
    let liTag = "";

    // Dias mês anterior
    for (let i = firstDayofMonth; i > 0; i--) {
        liTag += `<li class="inactive">${new Date(currCalYear, currCalMonth, 0).getDate() - i + 1}</li>`;
    }
    // Dias mês atual
    for (let i = 1; i <= lastDateofMonth; i++) {
        let isToday = i === new Date().getDate() && currCalMonth === new Date().getMonth() && currCalYear === new Date().getFullYear() ? "today" : "";
        let isSelected = i === selectedDate.getDate() && currCalMonth === selectedDate.getMonth() && currCalYear === selectedDate.getFullYear() ? "active" : "";
        if (isSelected) isToday = ""; // Prioridade visual para seleção

        liTag += `<li class="${isToday} ${isSelected}" onclick="selectDayAndClose(${i})">${i}</li>`;
    }
    // Dias próximo mês
    for (let i = lastDayofMonthIndex; i < 6; i++) {
        liTag += `<li class="inactive">${i - lastDayofMonthIndex + 1}</li>`;
    }

    if (monthTxt) monthTxt.innerText = monthsFull[currCalMonth];
    if (yearTxt) yearTxt.innerText = currCalYear;
    daysTag.innerHTML = liTag;
}

function changeCalMonth(delta) {
    currCalMonth += delta;
    if (currCalMonth < 0 || currCalMonth > 11) {
        const d = new Date(currCalYear, currCalMonth, 1);
        currCalMonth = d.getMonth();
        currCalYear = d.getFullYear();
    }
    renderCalendarGrid();
}

function selectDayAndClose(day) {
    selectedDate = new Date(currCalYear, currCalMonth, day);
    loadCalendarData(); // Chama PHP pois pode ter mudado o mês/ano
    toggleCalendar();   // Fecha modal
}

// Input Manual
function maskDateInput(e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
    if (v.length > 5) v = v.slice(0, 5) + '/' + v.slice(5);
    e.target.value = v;
}

// =============================================================
// SUBSTITUA A FUNÇÃO commitManualDate POR ESTA VERSÃO MELHORADA
// E ADICIONE A FUNÇÃO triggerInputError LOGO ABAIXO DELA
// =============================================================

function commitManualDate() {
    const input = document.getElementById('manualDateInput');
    const v = input.value;

    // 1. Validação básica de tamanho
    if (v.length < 10) {
        triggerInputError();
        return;
    }

    const day = parseInt(v.slice(0, 2), 10);
    // Subtrai 1 porque o objeto Date usa meses de 0 a 11
    const monthIndex = parseInt(v.slice(3, 5), 10) - 1;
    const year = parseInt(v.slice(6, 10), 10);

    // 2. Validação de Mês (deve ser entre 0 e 11)
    if (monthIndex < 0 || monthIndex > 11 || isNaN(monthIndex)) {
        triggerInputError();
        return;
    }

    // 3. Validação Inteligente de Dias (inclui Fevereiro e Bissextos)
    // O dia '0' do próximo mês retorna o último dia do mês atual.
    const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();

    if (day < 1 || day > daysInMonth || isNaN(day)) {
        // Ex: Se tentar dia 30 de Fevereiro, daysInMonth será 28 ou 29, e vai cair aqui.
        triggerInputError();
        return;
    }

    // Se passou por tudo, sucesso!
    currCalMonth = monthIndex;
    currCalYear = year;
    selectDayAndClose(day);
    input.value = "";
}

// --- FUNÇÃO AUXILIAR PARA A ANIMAÇÃO DE ERRO ---
function triggerInputError() {
    const wrapper = document.querySelector('.input-wrapper');
    // Adiciona a classe que faz chacoalhar
    wrapper.classList.add('error-shake');

    // Remove a classe depois que a animação termina (400ms)
    // para que possa chacoalhar de novo se clicar novamente
    setTimeout(() => {
        wrapper.classList.remove('error-shake');
    }, 400);
}

// ===============================
// 4. INTERFACE E GRÁFICOS
// ===============================

function toggleInstructorCard() {
    const card = document.getElementById('instructorCard');
    if (card) card.classList.toggle('active');
}

function exportData() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const btn = document.querySelector('.btn-export');
    if (!btn) return;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = 'Gerando PDF...';
    btn.style.color = '#E30613';

    // Configurações do PDF
    doc.setFontSize(18);
    doc.text("Relatório de Ocorrências - EPI Guard", 14, 20);
    doc.setFontSize(11);
    doc.setTextColor(100);
    doc.text(`Data de Geração: ${new Date().toLocaleDateString()}`, 14, 30);

    // Pega as ocorrências atuais do mês selecionado
    const currentMonth = selectedDate.getMonth() + 1;
    const currentYear = selectedDate.getFullYear();

    fetch(`../apis/api.php?action=modal_details&month=${currentMonth}&year=${currentYear}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                alert("Nenhuma ocorrência encontrada para exportar neste mês.");
                btn.innerHTML = originalHTML;
                btn.style.color = '';
                return;
            }

            const head = [['Data', 'Aluno', 'EPI', 'Hora', 'Status']];
            const body = data.map(row => [
                row.data,
                row.aluno,
                row.epis,
                row.hora,
                row.status_formatado
            ]);

            doc.autoTable({
                startY: 35,
                head: head,
                body: body,
                theme: 'striped',
                headStyles: { fillColor: [227, 6, 19] } // Vermelho SENAI
            });

            doc.save(`relatorio_epi_${currentMonth}_${currentYear}.pdf`);

            btn.innerHTML = originalHTML;
            btn.style.color = '';
        })
        .catch(err => {
            console.error(err);
            alert("Erro ao gerar PDF.");
            btn.innerHTML = originalHTML;
            btn.style.color = '';
        });
}

// Modal de Detalhes (Vem do clique no gráfico)
function openDetailModal(monthIndex, monthName, epiName = '') {
    const modal = document.getElementById('detailModal');
    const title = document.getElementById('modalMonthTitle');
    const tbody = document.getElementById('modalTableBody');

    if (!modal) return;

    const realMonth = monthIndex + 1;
    const currentYear = new Date().getFullYear();

    let displayTitle = `${monthName} de ${currentYear}`;
    if (epiName) {
        displayTitle += ` - Filtro: ${epiName}`;
    }

    title.innerText = displayTitle;
    modal.style.display = ''; // Limpa qualquer display: none residual
    modal.classList.add('open');

    let url = `../apis/api.php?action=modal_details&month=${realMonth}&year=${currentYear}`;
    if (epiName) {
        url += `&epi=${encodeURIComponent(epiName)}`;
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            tbody.innerHTML = '';
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">Nenhum registro encontrado.</td></tr>';
                return;
            }
            data.forEach(row => {
                const statusTexto = row.status_formatado || row.status;
                let classeStatus = statusTexto === 'Pendente' ? 'status-pendente' : 'status-resolvido';

                // Atributos extras se for Pendente: cursor pointer e redirecionamento para ocorrencias.php
                let extraAttrs = '';
                if (statusTexto === 'Pendente') {
                    const params = new URLSearchParams({
                        ocorrencia_id: row.ocorrencia_id,
                        aluno_id: row.aluno_id,
                        epi: row.epis,
                        data: row.data,
                        hora: row.hora
                    });
                    extraAttrs = `style="cursor: pointer;" onclick="window.location.href='ocorrencias.php?${params.toString()}'" title="Clique para resolver ocorrência"`;
                }

                tbody.innerHTML += `
                    <tr>
                        <td>${row.data}</td>
                        <td style="font-weight:500;">${row.aluno}</td>
                        <td>${row.epis}</td>
                        <td>${row.hora}</td>
                        <td><span class="status-badge ${classeStatus}" ${extraAttrs}>${statusTexto}</span></td>
                    </tr>`;
            });
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5" style="color:red; text-align:center">Erro na conexão.</td></tr>';
        });
}

function loadCharts() {
    fetch('../apis/api.php?action=charts')
        .then(res => res.json())
        .then(response => {
            // Verifica se a resposta é um erro de sessão
            if (response.status === 'session_expired' || response.status === 'not_logged') {
                console.warn("Sessão expirada ao carregar gráficos.");
                window.location.href = 'index.php';
                return;
            }

            // Destrói instâncias anteriores se existirem para evitar erros de Canvas
            if (mainChartInstance) mainChartInstance.destroy();
            if (doughnutChartInstance) doughnutChartInstance.destroy();

            // BAR CHART
            const ctxMain = document.getElementById('mainChart').getContext('2d');
            mainChartInstance = new Chart(ctxMain, {
                type: 'bar',
                data: {
                    labels: monthsFull,
                    datasets: [
                        { label: 'Capacete', data: response.bar.capacete, backgroundColor: '#E30613', borderRadius: 4 },
                        { label: 'Óculos', data: response.bar.oculos, backgroundColor: '#1F2937', borderRadius: 4 },
                        { label: 'Total', data: response.bar.total, backgroundColor: '#9CA3AF', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    onClick: (evt, active, chart) => {
                        if (active.length > 0) {
                            const barIndex = active[0].index;
                            const datasetIndex = active[0].datasetIndex;
                            const label = chart.data.datasets[datasetIndex].label;

                            // Se clicou no "Total", não passa filtro de EPI
                            const filterEPI = label === 'Total' ? '' : label;

                            openDetailModal(barIndex, monthsFull[barIndex], filterEPI);
                        }
                    }
                }
            });

            // DOUGHNUT CHART
            const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
            doughnutChartInstance = new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: response.doughnut.labels,
                    datasets: [{
                        data: response.doughnut.data,
                        backgroundColor: ['#E30613', '#1F2937', '#9CA3AF'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    onClick: (evt, active, chart) => {
                        if (active.length > 0) {
                            const index = active[0].index;
                            const label = chart.data.labels[index];
                            openDetailModal(selectedDate.getMonth(), monthsFull[selectedDate.getMonth()], label);
                        }
                    }
                }
            });
        })
        .catch(err => {
            console.error('Erro gráficos:', err);
            // Tenta recarregar uma vez após um pequeno delay se for erro de rede/timeout
            setTimeout(() => {
                if (!mainChartInstance) loadCharts();
            }, 3000);
        });
}


// =============================================================
// LÓGICA DOS DROPDOWNS (MÊS E ANO)
// Cole isso no final do dashboard.js ou dentro da seção de Helpers
// =============================================================

// 1. Alternar visualização da lista de MESES
function toggleMonthList() {
    const drop = document.getElementById('monthDropdown');
    const yearDrop = document.getElementById('yearDropdown');

    // Fecha o de ano se estiver aberto
    if (yearDrop) yearDrop.classList.remove('active');

    if (!drop.classList.contains('active')) {
        // Preenche a lista antes de mostrar
        let html = '';
        monthsFull.forEach((m, index) => {
            const isSelected = index === currCalMonth ? 'selected' : '';
            html += `<div class="dropdown-item ${isSelected}" onclick="selectMonth(${index})">${m}</div>`;
        });
        drop.innerHTML = html;
        drop.classList.add('active');
    } else {
        drop.classList.remove('active');
    }
}

// 2. Alternar visualização da lista de ANOS
function toggleYearList() {
    const drop = document.getElementById('yearDropdown');
    const monthDrop = document.getElementById('monthDropdown');

    // Fecha o de mês se estiver aberto
    if (monthDrop) monthDrop.classList.remove('active');

    if (!drop.classList.contains('active')) {
        let html = '';
        const currentYear = new Date().getFullYear();
        // Gera 5 anos para trás e 5 para frente
        for (let i = currentYear - 5; i <= currentYear + 5; i++) {
            const isSelected = i === currCalYear ? 'selected' : '';
            html += `<div class="dropdown-item ${isSelected}" onclick="selectYear(${i})">${i}</div>`;
        }
        drop.innerHTML = html;
        drop.classList.add('active');
    } else {
        drop.classList.remove('active');
    }
}

// 3. Ação ao clicar em um Mês
function selectMonth(index) {
    currCalMonth = index;
    renderCalendarGrid(); // Atualiza o grid
    document.getElementById('monthDropdown').classList.remove('active'); // Fecha menu
}

// 4. Ação ao clicar em um Ano
function selectYear(year) {
    currCalYear = year;
    renderCalendarGrid(); // Atualiza o grid
    document.getElementById('yearDropdown').classList.remove('active'); // Fecha menu
}

// 5. Fechar dropdowns se clicar fora (UX Importante)
window.addEventListener('click', function (e) {
    const monthContainer = document.getElementById('monthSelector');
    const yearContainer = document.getElementById('yearSelector');

    // Se o clique NÃO foi dentro do container do mês, fecha o dropdown do mês
    if (monthContainer && !monthContainer.contains(e.target)) {
        const drop = document.getElementById('monthDropdown');
        if (drop) drop.classList.remove('active');
    }

    // Se o clique NÃO foi dentro do container do ano, fecha o dropdown do ano
    if (yearContainer && !yearContainer.contains(e.target)) {
        const drop = document.getElementById('yearDropdown');
        if (drop) drop.classList.remove('active');
    }
});

function highlightDaily(periodo) {
    window.location.href = 'infracoes.php?filtro=' + periodo;
}
// Função extra para atualizar as porcentagens dinamicamente
function refreshBadgesJS(currentVal, previousVal, elementId) {
    const badge = document.getElementById(elementId);
    if (!badge) return;

    let percent = 0;
    if (previousVal > 0) {
        percent = Math.round(((currentVal - previousVal) / previousVal) * 100);
    } else {
        percent = currentVal * 100;
    }

    // Define a classe de cor e a seta
    const isUp = percent >= 0;
    badge.className = `badge ${isUp ? 'up' : 'down'}`;
    badge.innerHTML = `${isUp ? '↗' : '↘'} ${Math.abs(percent)}%`;
}

// --- MONITOR DE MUDANÇAS (FUNÇÃO EXTRA) ---
// --- MONITOR DE MUDANÇAS (FUNÇÃO EXTRA) ---
const observer = new MutationObserver(() => {
    // 1. Pega os valores atuais (KPIs)
    const totalHoje = parseInt(document.getElementById('kpiDia')?.innerText) || 0;
    const totalSemana = parseInt(document.getElementById('kpiSemana')?.innerText) || 0;
    const totalMes = parseInt(document.getElementById('kpiMes')?.innerText) || 0;

    // 2. Cálculo dos Períodos Anteriores para comparação
    const datePrevDay = new Date(selectedDate);
    datePrevDay.setDate(datePrevDay.getDate() - 1);

    // Ontem
    const totalOntem = allOccurrences.filter(item => {
        const itemDate = new Date((item.full_date || item.data_hora || item.date).replace(/-/g, '/'));
        return isSameDay(datePrevDay, itemDate);
    }).length;

    // 3. Atualiza as Badges chamando a sua função refreshBadgesJS
    refreshBadgesJS(totalHoje, totalOntem, 'badgeDia');

    // Nota: Como a lógica de "semana anterior" exige um cálculo de calendário mais complexo,
    // por enquanto o observer vai resetar as outras badges ou mantê-las consistentes.
});

// Configura o observador para vigiar o texto dos KPIs
// Usamos characterData e childList para garantir que qualquer mudança de texto dispare
const config = { childList: true, characterData: true, subtree: true };

if (document.getElementById('kpiDia')) observer.observe(document.getElementById('kpiDia'), config);
if (document.getElementById('kpiSemana')) observer.observe(document.getElementById('kpiSemana'), config);
if (document.getElementById('kpiMes')) observer.observe(document.getElementById('kpiMes'), config);


function updatePercentagesDinamicamente() {
    // 1. Definir datas de comparação
    const datePrevDay = new Date(selectedDate);
    datePrevDay.setDate(datePrevDay.getDate() - 1);

    const startOfSelectedWeek = new Date(selectedDate);
    startOfSelectedWeek.setDate(selectedDate.getDate() - selectedDate.getDay());
    const datePrevWeek = new Date(startOfSelectedWeek);
    datePrevWeek.setDate(datePrevWeek.getDate() - 7);

    // 2. Contar ocorrências nos períodos anteriores
    let totalOntem = 0;
    let totalSemanaPassada = 0;

    allOccurrences.forEach(item => {
        const itemDate = new Date((item.full_date || item.data_hora || item.date).replace(/-/g, '/'));

        if (isSameDay(datePrevDay, itemDate)) totalOntem++;
        if (isSameWeek(datePrevWeek, itemDate)) totalSemanaPassada++;
    });

    // 3. Pegar os valores atuais que já estão na tela
    const totalHoje = parseInt(document.getElementById('kpiDia').innerText) || 0;
    const totalSemana = parseInt(document.getElementById('kpiSemana').innerText) || 0;

    // 4. Chamar a função de badges (aquela que você já tem no arquivo)
    refreshBadgesJS(totalHoje, totalOntem, 'badgeDia');
    refreshBadgesJS(totalSemana, totalSemanaPassada, 'badgeSemana');
}

function closeModal() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.classList.remove('open');
        modal.style.display = '';
    }
}

function openAlunosModal() {
    const modal = document.getElementById('alunosRankingModal');
    if (modal) {
        modal.style.display = ''; // Limpa display: none
        modal.classList.add('open');
    }
}

function closeAlunosModal() {
    const modal = document.getElementById('alunosRankingModal');
    if (modal) {
        modal.classList.remove('open');
    }
}

// parte das notifcaçoes:
// =========================================================
// SISTEMA DE NOTIFICAÇÕES EM TEMPO REAL
// =========================================================

// 1. Variável para o sistema "lembrar" qual foi o último ID que ele já mostrou na tela
// =========================================================
// SISTEMA DE NOTIFICAÇÕES EM TEMPO REAL
// =========================================================

let ultimoIdNotificacao = 0;

// 1. Função Visual: Cria o card com as classes do seu CSS
function mostrarNotificacao(aluno, epi) {
    const container = document.getElementById('notification-container');
    if (!container) return;

    // Tenta tocar o som. 
    // DICA: Se o JS está na pasta /js, talvez precise de ../som/notificacao.mp3
    const audio = new Audio('../som/notificacao.mp3');

    audio.play().catch(error => {
        // Se o erro for NotSupportedError, o caminho 'som/notificacao.mp3' está errado.
        console.error("Erro de áudio:", error.name, "Verifique se o arquivo existe na pasta 'som'");
    });

    const agora = new Date();
    const horario = agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    const toast = document.createElement('div');
    toast.className = 'toast';

    toast.innerHTML = `
        <div class="toast-icon">
            <i data-lucide="alert-triangle"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">Infração Detectada</div>
            <div class="toast-message"><b>${aluno}</b> • Sem ${epi}</div>
            <span class="toast-time">${horario}</span>
        </div>
    `;

    container.appendChild(toast);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons({ root: toast });
    }

    if (typeof updateToastWidths === 'function') updateToastWidths();

    setTimeout(() => {
        if (typeof removeToast === 'function') removeToast(toast);
        else toast.remove();
    }, 5000);
}

// Desbloqueia o áudio automaticamente no primeiro clique do instrutor
document.addEventListener('click', function unlockAudio() {
    const dummyAudio = new Audio('../som/notificacao.mp3');
    dummyAudio.volume = 0; // Toca mudo só para ganhar permissão
    dummyAudio.play().then(() => {
        console.log("🔊 Sistema de áudio ativado com sucesso!");
        document.removeEventListener('click', unlockAudio);
    }).catch(e => console.error("Erro ao ativar som:", e));
}, { once: true });

// 2. Função Auxiliar: Faz o card sair suavemente e organiza os restantes
function removeToast(toastElement) {
    if (toastElement.classList.contains('removing')) return;
    toastElement.classList.add('removing'); // Ativa a animação do seu CSS

    setTimeout(() => {
        if (toastElement.parentElement) {
            toastElement.parentElement.removeChild(toastElement);
            updateToastWidths();
        }
    }, 350); // 350ms é o tempo da animação (0.3s)
}

// 3. Função Auxiliar: Faz o efeito empilhado (cards de baixo ficam menores)
function updateToastWidths() {
    const container = document.getElementById('notification-container');
    const toasts = Array.from(container.querySelectorAll('.toast:not(.removing)'));
    if (toasts.length === 0) return;

    const midIndex = (toasts.length - 1) / 2;

    toasts.forEach((toast, index) => {
        const distanceFromCenter = Math.abs(index - midIndex);
        const percentage = 100 - (distanceFromCenter * 5); // Diminui 5% de largura
        toast.style.width = `${Math.max(percentage, 75)}%`; // Nunca fica menor que 75%
    });
}

// 4. O Motor (Fetch no PHP) - Mantivemos a sua lógica que já funcionava!
function verificarNovasOcorrencias() {
    fetch(`../php/check_notificacoes.php?last_id=${ultimoIdNotificacao}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'init') {
                ultimoIdNotificacao = data.last_id;
                return;
            }

            if (data.status === 'success' && data.dados.length > 0) {
                data.dados.forEach(ocorrencia => {
                    if (ocorrencia.id > ultimoIdNotificacao) {

                        // Nota: O som de notificação agora é gerenciado globalmente pelo notifications.js

                        mostrarNotificacao(ocorrencia.aluno, ocorrencia.epi_nome);
                        ultimoIdNotificacao = ocorrencia.id;
                    }
                });

                if (typeof loadCalendarData === 'function') loadCalendarData();
                if (typeof updateKPICards === 'function') updateKPICards();
            }
        })
        .catch(err => console.error("Erro:", err));
}
// <------------------------------------------>//
setInterval(verificarNovasOcorrencias, 5000);
verificarNovasOcorrencias();


document.addEventListener('click', function () {
    const testeAudio = new Audio('../som/notificacao.mp3');
    testeAudio.volume = 0;
    testeAudio.play()
        .then(() => console.log("✅ Som carregado e áudio liberado!"))
        .catch(err => console.warn("❌ Arquivo não encontrado no caminho 'som/notificacao.mp3'"));
}, { once: true });

// Basta o instrutor clicar em qualquer lugar do dashboard uma única vez
// que o navegador libera todos os próximos sons do fetch automático.
document.addEventListener('click', function liberarAudio() {
    const audioTest = new Audio('../som/notificacao.mp3');
    audioTest.volume = 0; // Toca mudo
    audioTest.play();
    document.removeEventListener('click', liberarAudio);
}, { once: true });



// Nota: A lógica de transições de página agora é gerenciada globalmente pelo transitions.js
