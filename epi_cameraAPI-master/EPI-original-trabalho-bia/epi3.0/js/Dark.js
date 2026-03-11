// ==========================================
// SISTEMA DE TEMA (DARK MODE)
// ==========================================

// 1. Aplica o tema salvo assim que a página carrega
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    const toggleBtn = document.getElementById('toggle-darkmode'); // O botão da página de configs

    if (savedTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
        if (toggleBtn) toggleBtn.checked = true; // Deixa o botão ativado se for dark mode
    } else {
        document.body.removeAttribute('data-theme');
        if (toggleBtn) toggleBtn.checked = false;
    }
});

// 2. Alterna o tema quando o usuário clica no botão
window.toggleTheme = function() {
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    
    // Aplica a mudança
    if (newTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
    } else {
        document.body.removeAttribute('data-theme');
    }
    
    // Salva no navegador
    localStorage.setItem('theme', newTheme);
    
    // Mostra a notificação
    showThemeNotification(newTheme);
}

// 3. Cria e exibe a notificação na tela (Toasts)
function showThemeNotification(theme) {
    const container = document.getElementById('notification-container');
    if (!container) return; // Se não houver o container na página, não faz nada
    
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
        <div class="toast-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4M12 8h.01"></path>
            </svg>
        </div>
        <div class="toast-content">
            <span class="toast-title">Tema ${theme === 'dark' ? 'escuro' : 'claro'} ativado</span>
            <span class="toast-message">Aparência alterada com sucesso</span>
            <span class="toast-time">agora</span>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Remove a notificação após 3 segundos
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// 4. Sincroniza o tema se o usuário mudar em outra aba aberta
window.addEventListener('storage', function(e) {
    if (e.key === 'theme') {
        const toggleBtn = document.getElementById('toggle-darkmode');
        
        if (e.newValue === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            if (toggleBtn) toggleBtn.checked = true;
        } else {
            document.body.removeAttribute('data-theme');
            if (toggleBtn) toggleBtn.checked = false;
        }
    }
});

// 1. Função para carregar o tema assim que a página abre
function loadTheme() {
    // Pega a preferência salva no navegador
    const savedTheme = localStorage.getItem('theme');
    const toggleCheckbox = document.getElementById('toggle-darkmode');

    // Se estiver salvo como 'dark', aplica a classe no body
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        // Se estivermos na página de configurações, deixa o "switch" marcado
        if (toggleCheckbox) {
            toggleCheckbox.checked = true;
        }
    }
}

// 2. Função que é chamada quando o usuário clica no switch
function toggleTheme() {
    const body = document.body;
    
    // Alterna a classe 'dark-mode' no body. Retorna true se adicionou, false se removeu.
    const isDarkMode = body.classList.toggle('dark-mode');

    // Salva a nova preferência no localStorage
    if (isDarkMode) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
}
// 1. Função para carregar o tema assim que a página abre
function loadTheme() {
    // Pega a preferência salva no navegador
    const savedTheme = localStorage.getItem('theme');
    const toggleCheckbox = document.getElementById('toggle-darkmode');

    // Se estiver salvo como 'dark', aplica a classe no body
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        // Se estivermos na página de configurações, deixa o "switch" marcado
        if (toggleCheckbox) {
            toggleCheckbox.checked = true;
        }
    }
}

// 2. Função que é chamada quando o usuário clica no switch
function toggleTheme() {
    const body = document.body;
    
    // Alterna a classe 'dark-mode' no body. Retorna true se adicionou, false se removeu.
    const isDarkMode = body.classList.toggle('dark-mode');

    // Salva a nova preferência no localStorage
    if (isDarkMode) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
}

// Executa o carregamento do tema assim que o HTML terminar de carregar
document.addEventListener('DOMContentLoaded', loadTheme);