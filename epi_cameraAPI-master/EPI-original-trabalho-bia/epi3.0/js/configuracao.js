// Inicializa ícones do Lucide
lucide.createIcons();

// ==========================================
// 1. Interatividade (Links nos Cards)
// ==========================================
function toggleLinkAbility(checkboxElement) {
    // Pegamos direto o status do botão que foi clicado
    const linksEnabled = checkboxElement.checked;

    // Salva a preferência no navegador
    localStorage.setItem('linksEnabled', linksEnabled);

    // Adiciona ou remove a classe dos cards
    const cards = document.querySelectorAll('.card, .violation-card');
    cards.forEach(c => {
        if (linksEnabled) c.classList.add('clickable');
        else c.classList.remove('clickable');
    });
}

function handleCardClick(cardId) {
    // Verifica no storage se os links estão liberados
    const isEnabled = localStorage.getItem('linksEnabled') === 'true';
    if (isEnabled) {
        alert(`Redirecionando para detalhes de: ${cardId}`);
        // window.location.href = 'infracoes.php?filtro=' + cardId;
    }
}

// ==========================================
// 3 e 4. Interface (Porcentagem e Status)
// ==========================================
function togglePercentDisplay(checkbox) {
    localStorage.setItem('showPercentages', checkbox.checked);
    // Aplica na hora (função global em global.js)
    if (typeof applyPercentageVisibility === 'function') {
        applyPercentageVisibility();
    }
}

function toggleStatus() {
    const isChecked = document.getElementById('toggle-status').checked;
    localStorage.setItem('showStatusBadges', isChecked);
    // Aplica na hora (função global em global.js)
    if (typeof applyGlobalSettings === 'function') {
        applyGlobalSettings();
    }
}

// ==========================================
// 5 e 6. Gráficos (Tipo e Cor)
// ==========================================
function changeChartType(type) {
    document.getElementById('chart-donut').style.display = 'none';
    document.getElementById('chart-bar').style.display = 'none';
    document.getElementById('chart-line').style.display = 'none';

    if (type === 'donut') document.getElementById('chart-donut').style.display = 'flex';
    if (type === 'bar') document.getElementById('chart-bar').style.display = 'flex';
    if (type === 'line') document.getElementById('chart-line').style.display = 'block';

    localStorage.setItem('chartType', type);
}

function changeChartColor(color) {
    document.documentElement.style.setProperty('--chart-main-color', color);
    localStorage.setItem('chartColor', color);
}

function toggleSound(checkbox) {
    localStorage.setItem('soundEnabled', checkbox.checked);
}

// Inicializa os checkboxes com os valores do localStorage
document.addEventListener('DOMContentLoaded', () => {
    const soundToggle = document.getElementById('toggle-sound');
    if (soundToggle) {
        const soundEnabled = localStorage.getItem('soundEnabled') !== 'false';
        soundToggle.checked = soundEnabled;
    }

    const linksToggle = document.getElementById('toggle-link');
    if (linksToggle) {
        linksToggle.checked = localStorage.getItem('linksEnabled') === 'true';
    }

    const percentToggle = document.getElementById('toggle-percent');
    if (percentToggle) {
        percentToggle.checked = localStorage.getItem('showPercentages') !== 'false';
    }

    const statusToggle = document.getElementById('toggle-status');
    if (statusToggle) {
        statusToggle.checked = localStorage.getItem('showStatusBadges') !== 'false';
    }

    // Inicializa visibilidade globalmente ao carregar a página
    applyPercentageVisibility();
});
