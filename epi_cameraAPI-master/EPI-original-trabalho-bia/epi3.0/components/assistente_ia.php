<?php
/* //// CONEXÃO COM O BANCO DE DADOS //// */

// O projeto já possui um arquivo de configuração centralizado. 
// Vamos tentar usar a conexão que já existe para evitar erro de porta (3308).
if (!isset($conn)) {
    $dbConfig = __DIR__ . '/../config/database.php';
    if (file_exists($dbConfig)) {
        include_once $dbConfig;
    }
}

$dadosEPIs = [];
$dadosAlunos = [];
$dadosTempo = [];
$dadosCursos = [];
$dadosSancoes = [];
$dadosTurnos = [];
$dadosSupervisores = [];

// Só executa se a conexão for bem sucedida
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    try {
        // 1. RANKING DE EPIs (Mais vs Menos usados/infratados)
        $sqlEpis = "SELECT e.nome, COUNT(o.id) as total_ocorrencias 
                    FROM epis e 
                    LEFT JOIN ocorrencias o ON e.id = o.epi_id 
                    GROUP BY e.id, e.nome 
                    ORDER BY total_ocorrencias DESC"; 
        $resultEpis = $conn->query($sqlEpis);
        if ($resultEpis && $resultEpis->num_rows > 0) {
            while($row = $resultEpis->fetch_assoc()) {
                $dadosEPIs[] = $row;
            }
        }

        // 2. RANKING DE ALUNOS (Maiores infratores)
        // MUDANÇA: Usei LEFT JOIN no cursos para evitar lista vazia se a tabela cursos estivesse incompleta
        $sqlAlunos = "SELECT a.nome, COALESCE(c.nome, 'Sem Curso') as curso, COUNT(o.id) as total_infracoes 
                      FROM alunos a 
                      LEFT JOIN cursos c ON a.curso_id = c.id
                      LEFT JOIN ocorrencias o ON a.id = o.aluno_id 
                      GROUP BY a.id, a.nome, c.nome
                      ORDER BY total_infracoes DESC"; 
        $resultAlunos = $conn->query($sqlAlunos);
        if ($resultAlunos && $resultAlunos->num_rows > 0) {
            while($row = $resultAlunos->fetch_assoc()) {
                $dadosAlunos[] = $row;
            }
        }

        // 3. BUSCA OCORRÊNCIAS NO ÚLTIMO SEMESTRE (DADOS TEMPORAIS)
        $sqlTempo = "SELECT DATE(data_hora) as data, COUNT(id) as total 
                     FROM ocorrencias 
                     WHERE data_hora >= DATE_SUB(NOW(), INTERVAL 180 DAY)
                     GROUP BY DATE(data_hora) 
                     ORDER BY data ASC"; 
        $resultTempo = $conn->query($sqlTempo);
        
        $totalOcorrencias30d = 0;
        $diaRecorde = ['data' => 'N/A', 'total' => 0];
        $diaTranquilo = ['data' => 'N/A', 'total' => 999];

        if ($resultTempo && $resultTempo->num_rows > 0) {
            while($row = $resultTempo->fetch_assoc()) {
                $dadosTempo[] = $row;
                $totalOcorrencias30d += $row['total'];
                
                if ($row['total'] > $diaRecorde['total']) {
                    $diaRecorde = $row;
                }
                if ($row['total'] < $diaTranquilo['total']) {
                    $diaTranquilo = $row;
                }
            }
        }
        
        // Formatar dados extras de resumo
        $resumoGeral = [
            'total_30_dias' => $totalOcorrencias30d,
            'media_diaria' => count($dadosTempo) > 0 ? round($totalOcorrencias30d / count($dadosTempo), 2) : 0,
            'dia_com_mais_infracoes' => $diaRecorde,
            'dia_com_menos_infracoes' => $diaTranquilo['total'] == 999 ? ['data' => 'N/A', 'total' => 0] : $diaTranquilo
        ];

        // 4. SANÇÕES POR TIPO
        $sqlSancoes = "SELECT tipo, COUNT(id) as total FROM acoes_ocorrencia GROUP BY tipo ORDER BY total DESC";
        $resultSancoes = $conn->query($sqlSancoes);
        if ($resultSancoes) {
            while($row = $resultSancoes->fetch_assoc()) {
                $dadosSancoes[] = $row;
            }
        }

        // 5. DISTRIBUIÇÃO POR TURNO (ALUNOS)
        $sqlTurnos = "SELECT turno, COUNT(id) as total FROM alunos WHERE turno IS NOT NULL GROUP BY turno ORDER BY total DESC";
        $resultTurnos = $conn->query($sqlTurnos);
        if ($resultTurnos) {
            while($row = $resultTurnos->fetch_assoc()) {
                $dadosTurnos[] = $row;
            }
        }

        // 6. RANKING DE SUPERVISORES (QUEM MAIS REGISTRA)
        $sqlSupervisores = "SELECT u.nome, u.cargo, COUNT(ao.id) as total_acoes 
                           FROM usuarios u 
                           JOIN acoes_ocorrencia ao ON u.id = ao.usuario_id 
                           GROUP BY u.id, u.nome, u.cargo 
                           ORDER BY total_acoes DESC";
        $resultSupervisores = $conn->query($sqlSupervisores);
        if ($resultSupervisores) {
            while($row = $resultSupervisores->fetch_assoc()) {
                $dadosSupervisores[] = $row;
            }
        }

        // 7. RANKING DE CURSOS (INCLUI CURSOS SEM INFRAÇÕES)
        $sqlCursosRanking = "SELECT c.nome as curso, COUNT(o.id) as total_infracoes 
                            FROM cursos c 
                            LEFT JOIN alunos a ON c.id = a.curso_id 
                            LEFT JOIN ocorrencias o ON a.id = o.aluno_id 
                            GROUP BY c.id, c.nome 
                            ORDER BY total_infracoes DESC";
        $resultCursosRanking = $conn->query($sqlCursosRanking);
        if ($resultCursosRanking) {
            while($row = $resultCursosRanking->fetch_assoc()) {
                $dadosCursos[] = $row;
            }
        }

    } catch (Throwable $e) {
        error_log("Erro no Assistente IA: " . $e->getMessage());
    }
}

// Converte os dados para JSON (vazio se falhar a conexão)
$jsonEpis = json_encode($dadosEPIs);
$jsonAlunos = json_encode($dadosAlunos);
$jsonTempo = json_encode($dadosTempo);
$jsonResumo = json_encode($resumoGeral ?? []);
$jsonSancoes = json_encode($dadosSancoes);
$jsonTurnos = json_encode($dadosTurnos);
$jsonSupervisores = json_encode($dadosSupervisores);
$jsonCursos = json_encode($dadosCursos);

/* //// FIM DA CONEXÃO DO BANCO DE DADOS //// */
?>

<!-- //// INCLUSÃO DE BIBLIOTECAS PARA PDF //// -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* //// ESTILOS DO ASSISTENTE IA //// */

.ai-trigger-btn {
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.ai-trigger-btn:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
}

/* Painel Popover (Janela do Chat) */
.ai-assistant-popover {
    position: fixed;
    bottom: 85px; 
    left: 20px; 
    width: 350px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    z-index: 9999;
    display: none; 
    flex-direction: column;
    font-family: 'Inter', sans-serif;
    overflow: hidden;
}

.ai-assistant-popover.open {
    display: flex;
}

.ai-header {
    background: #ffffff;
    color: #1f2937;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    font-size: 14px;
    border-bottom: 1px solid #e5e7eb;
}

.ai-header-icon {
    color: #E30613;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-close-btn {
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 18px;
    cursor: pointer;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ai-close-btn:hover {
    color: #4b5563;
}

.ai-chat-body {
    padding: 16px;
    height: 350px; 
    overflow-y: auto;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ai-message {
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.5;
    word-wrap: break-word;
    position: relative;
}

.ai-message.bot {
    background: #f1f5f9; 
    color: #0f172a;
    align-self: flex-start;
    max-width: 90%;
    border-bottom-left-radius: 2px;
}

.ai-message.user {
    background: #E30613; 
    color: white;
    align-self: flex-end;
    max-width: 90%;
    border-bottom-right-radius: 2px;
}

.ai-loading {
    align-self: flex-start;
    font-size: 12px;
    color: #64748b;
    font-style: italic;
    display: none;
    padding: 8px 14px;
    align-items: center;
    gap: 8px;
}

.ai-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid #e2e8f0;
    border-top-color: #E30613;
    border-radius: 50%;
    animation: ai-spin 0.8s linear infinite;
    display: inline-block;
}

@keyframes ai-spin {
    to { transform: rotate(360deg); }
}

.ai-input-area {
    padding: 12px 16px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
    background: #ffffff;
}

.ai-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
    transition: border-color 0.2s;
    color: #334155;
}
.ai-input::placeholder {
    color: #94a3b8;
}
.ai-input:focus {
    border-color: #E30613;
}

.ai-send-btn {
    background: #E30613;
    color: white;
    border: none;
    border-radius: 8px;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.ai-send-btn:hover {
    background: #dc2626;
}

.ai-message.bot strong {
    font-weight: 600;
}

/* //// ESTILO BOTÃO PDF //// */
.ai-pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #ef4444;
    cursor: pointer;
    margin-top: 8px;
    transition: all 0.2s;
}
.ai-pdf-btn:hover {
    background: #fef2f2;
    border-color: #fecaca;
    transform: translateY(-1px);
}

/* Container de Gráfico */
.ai-chart-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    margin-top: 10px;
    width: 100%;
}
</style>

<div class="ai-assistant-popover" id="aiAssistantPanel">
    <div class="ai-header">
        <div class="ai-header-icon">
            <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i> Assistente EPI Guard
        </div>
        <button class="ai-close-btn" onclick="toggleAssistenteIA()">
             <i data-lucide="x" style="width: 16px; height: 16px;"></i>
        </button>
    </div>
    
    <div class="ai-chat-body" id="aiChatBody">
        <div class="ai-message bot">
            Olá Administrador! O que deseja analisar sobre os dados de EPIs hoje?
        </div>
        <div class="ai-loading" id="aiLoading">Processando dados...</div>
    </div>
    
    <div class="ai-input-area">
        <input type="text" class="ai-input" id="aiInput" placeholder="Ex: Qual o EPI menos utilizado?" onkeypress="handleAIPress(event)">
        <button class="ai-send-btn" onclick="sendAIMessage()">
            <i data-lucide="send" style="width: 16px; height: 16px; margin-left: -2px;"></i>
        </button>
    </div>
</div>

<script>
/* //// INÍCIO CONFIGURAÇÃO DA IA REFINADA //// */

const GEMINI_API_KEY = "AIzaSyC2ESlhLY9dtybFoUf2DLIiywQWDgDR6b8";

// Dados Reais Consolidados
const dadosEPIsReais = <?php echo $jsonEpis; ?>;
const dadosAlunosReais = <?php echo $jsonAlunos; ?>;
const dadosTempoReais = <?php echo $jsonTempo; ?>;
const resumoEstatisicoReais = <?php echo $jsonResumo; ?>;
const dadosSancoesReais = <?php echo $jsonSancoes; ?>;
const dadosTurnosReais = <?php echo $jsonTurnos; ?>;
const dadosSupervisoresReais = <?php echo $jsonSupervisores; ?>;
const dadosCursosReais = <?php echo $jsonCursos; ?>;

function toggleAssistenteIA() {
    const panel = document.getElementById('aiAssistantPanel');
    panel.classList.toggle('open');
    if(panel.classList.contains('open')) {
        setTimeout(() => document.getElementById('aiInput').focus(), 100);
    }
}

function handleAIPress(event) {
    if (event.key === 'Enter') {
        sendAIMessage();
    }
}

function appendAIMessage(text, sender) {
    const chatBody = document.getElementById('aiChatBody');
    const loading = document.getElementById('aiLoading');
    const msgDiv = document.createElement('div');
    msgDiv.className = 'ai-message ' + sender;
    
    let chartConfig = null;
    let cleanText = text;

    // Detecção de Gráfico via Regex (Mais robusto que substring)
    if (sender === 'bot') {
        const chartRegex = /CHART_DATA\s*(\{.*\})/s;
        const match = text.match(chartRegex);
        
        if (match) {
            cleanText = text.replace(chartRegex, '').trim();
            try {
                // Remove possíveis blocos de código markdown que a IA possa ter inserido
                let jsonStr = match[1].replace(/```json|```/g, '').trim();
                chartConfig = JSON.parse(jsonStr);
            } catch(e) { 
                console.error("Erro ao processar JSON do gráfico:", e);
            }
        }
    }

    // Formatação básica de negrito e quebras de linha
    let formattedText = cleanText.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    msgDiv.innerHTML = formattedText;
    
    if (sender === 'bot') {
        // Botão PDF apenas se tiver conteúdo
        if (cleanText.length > 20) {
            const pdfBtn = document.createElement('button');
            pdfBtn.className = 'ai-pdf-btn';
            pdfBtn.innerHTML = '<i data-lucide="file-text" style="width: 12px; height: 12px;"></i> Exportar PDF';
            
            pdfBtn.onclick = () => exportToPDF(cleanText, chartConfig ? 'actual-chart-' + Date.now() : null);
            msgDiv.appendChild(pdfBtn);
        }

        // Renderização do Gráfico
        if (chartConfig) {
            const actualChartId = 'chart-' + Date.now();
            const container = document.createElement('div');
            container.className = 'ai-chart-container';
            container.innerHTML = `<canvas id="${actualChartId}"></canvas>`;
            msgDiv.appendChild(container);

            setTimeout(() => {
                const canvas = document.getElementById(actualChartId);
                if (canvas && typeof Chart !== 'undefined') {
                    new Chart(canvas, chartConfig);
                    // Atualiza o onclick do PDF para o ID correto
                    const btn = msgDiv.querySelector('.ai-pdf-btn');
                    if (btn) btn.onclick = () => exportToPDF(cleanText, actualChartId);
                }
            }, 200);
        }

        if (window.lucide) lucide.createIcons();
    }
    
    chatBody.insertBefore(msgDiv, loading);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* //// EXPORTAÇÃO PDF //// */
function exportToPDF(text, chartId = null) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Cabeçalho
    doc.setFontSize(22); doc.setTextColor(227, 6, 19); doc.text("EPI GUARD", 105, 20, { align: "center" });
    doc.setFontSize(16); doc.setTextColor(31, 41, 55); doc.text("Relatório Técnico de IA", 105, 30, { align: "center" });
    doc.setFontSize(10); doc.setTextColor(100, 116, 139); doc.text("Emissão: " + new Date().toLocaleString(), 105, 38, { align: "center" });
    doc.setDrawColor(229, 231, 235); doc.line(20, 45, 190, 45);
    
    // Texto do Relatório
    doc.setFontSize(11); doc.setTextColor(17, 24, 39);
    const cleanText = text.replace(/\*\*/g, '');
    const splitText = doc.splitTextToSize(cleanText, 170);
    doc.text(splitText, 20, 55);
    
    let currentY = 55 + (splitText.length * 6);

    // Se houver um gráfico, capturar como imagem e inserir no PDF
    if (chartId) {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            try {
                const imgData = canvas.toDataURL("image/png", 1.0);
                // Adiciona uma nova página se não houver espaço (simplificado)
                if (currentY > 200) {
                    doc.addPage();
                    currentY = 20;
                }
                doc.setFontSize(12); doc.setTextColor(227, 6, 19);
                doc.text("Análise Visual:", 20, currentY + 10);
                doc.addImage(imgData, 'PNG', 20, currentY + 15, 170, 90);
            } catch (e) {
                console.error("Erro ao incluir gráfico no PDF:", e);
            }
        }
    }

    doc.save("Relatorio_EPI_Guard_" + new Date().getTime() + ".pdf");
}

/* //// ENVIO PARA IA //// */
async function sendAIMessage(retryCount = 0) {
    const inputField = document.getElementById('aiInput');
    const userText = retryCount > 0 ? sendAIMessage._lastText : inputField.value.trim();
    if (!userText) return;
    
    // Armazena o texto para retentativas
    if (retryCount === 0) {
        sendAIMessage._lastText = userText;
        appendAIMessage(userText, 'user');
        inputField.value = '';
    }
    
    const loading = document.getElementById('aiLoading');
    loading.style.display = 'block';
    
    // Compactação de Dados (Top 5 para economizar tokens e evitar 429)
    const dadosCompactos = {
        top_epis: dadosEPIsReais.slice(0, 5),
        top_alunos: dadosAlunosReais.slice(0, 5),
        resumo: resumoEstatisicoReais,
        recentes: dadosTempoReais.slice(-15), // Últimos 15 registros
        top_cursos: dadosCursosReais.slice(0, 5),
        top_supervisores: dadosSupervisoresReais.slice(0, 5)
    };

    // Construção robusta do prompt (evita quebra de template literal)
    const systemPrompt = "Você é o Estrategista de Dados do sistema EPI Guard do SENAI. " +
                         "Data de hoje: " + new Date().toLocaleDateString('pt-BR') + ". " +
                         "Sua base de dados resumida: " + JSON.stringify(dadosCompactos) + ". " +
                         "REGRAS: 1. Responda de forma direta e profissional. 2. Use **negrito** nos dados principais. " +
                         "3. Se houver rankings ou comparações numéricas, gere um gráfico no final da mensagem EXATAMENTE neste formato: " +
                         "CHART_DATA {\"type\":\"bar\",\"data\":{\"labels\":[\"A\",\"B\"],\"datasets\":[{\"label\":\"Qtd\",\"data\":[10,20],\"backgroundColor\":\"#E30613\"}]},\"options\":{\"responsive\":true}} " +
                         "Usuário pergunta: " + userText;

    try {
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${GEMINI_API_KEY}`;
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contents: [{ parts: [{ text: systemPrompt }] }] })
        });
        
        // Lógica de Retry para erro 429
        if (response.status === 429 && retryCount < 2) {
            loading.innerHTML = `⚠️ Muitas requisições. Tentando novamente em 10s... (${retryCount + 1}/3)`;
            setTimeout(() => sendAIMessage(retryCount + 1), 10000);
            return;
        }
        
        if (!response.ok) {
            const errorInfo = await response.json().catch(() => ({}));
            loading.style.display = 'none';
            console.error("Erro API Gemini:", response.status, errorInfo);
            appendAIMessage("⚠️ Erro " + response.status + ": A API está temporariamente indisponível ou sua cota excedeu. Tente novamente em alguns minutos.", 'bot');
            return;
        }

        const data = await response.json();
        loading.style.display = 'none';
        loading.innerHTML = '<span class="ai-spinner"></span> Analisando dados...'; // Reseta texto
        
        if (data.candidates && data.candidates[0].content?.parts?.[0]?.text) {
            appendAIMessage(data.candidates[0].content.parts[0].text, 'bot');
        } else {
            console.error("Resposta inesperada:", data);
            appendAIMessage("❌ A IA não conseguiu processar sua pergunta. Tente reformulá-la.", 'bot');
        }
    } catch (error) {
        loading.style.display = 'none';
        loading.innerHTML = '<span class="ai-spinner"></span> Analisando dados...';
        console.error("Erro na comunicação com a IA:", error);
        appendAIMessage("⚠️ Erro de rede. Verifique sua conexão e a chave da API.", 'bot');
    }
}

if (window.lucide) {
    lucide.createIcons();
}
</script>