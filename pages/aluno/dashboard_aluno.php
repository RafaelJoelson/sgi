<?php
// Dashboard do Aluno
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_aluno.css">
<main class="container">
    <h2>Aluno(a): <?= htmlspecialchars($_SESSION['usuario']['nome'] . (isset($_SESSION['usuario']['sobrenome']) ? ' ' . $_SESSION['usuario']['sobrenome'] : '')) ?></h2>
    <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
    
    <form id="form-solicitacao" enctype="multipart/form-data">
        <label>Arquivo para impressão
            <input type="file" name="arquivo" id="arquivo" required accept=".pdf,.doc,.docx,.jpg,.png">
        </label>
        <label class="toggle-switch-label">
            <!-- O texto que o usuário lê -->
            <span>Solicitar cópia no balcão</span>

            <!-- O container do interruptor visual -->
            <div class="toggle-switch">
                <!-- O checkbox real, que fica escondido -->
                <input type="checkbox" id="solicitar_balcao" name="solicitar_balcao">
                
                <!-- O elemento que o CSS usa para desenhar o interruptor -->
                <span class="slider"></span>
            </div>
        </label>
        <label>Quantidade de cópias
            <input type="number" name="qtd_copias" min="1" max="100" required>
        </label>
        <label>Número de páginas
            <input type="number" name="qtd_paginas" id="qtd_paginas" min="1" max="500" required placeholder="Informe o nº de páginas">
        </label>
        <button type="submit">Enviar Solicitação</button>
    </form>

    <section id="status-solicitacoes">
        <h2>Minhas Solicitações Recentes</h2>
        <div id="tabela-solicitacoes"></div>
    </section>

    <button onclick="window.location.href='historico_solicitacoes.php'">Ver Histórico Completo</button>
</main>
<script>
// Funções para carregar cota, enviar formulário, etc. (permanecem as mesmas)
function carregarCota() {
    fetch('cota_aluno.php')
        .then(r => r.json())
        .then(data => {
            if(data.sucesso) {
                document.getElementById('cota-info').innerText = `Cota disponível: ${data.cota_disponivel} páginas`;
            } else {
                document.getElementById('cota-info').innerText = 'Não foi possível obter a cota.';
            }
        });
}
carregarCota();

// (Restante do seu JavaScript para envio do formulário e lógica do checkbox permanece aqui...)
const form = document.getElementById('form-solicitacao');
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    fetch('enviar_solicitacao.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.mensagem);
        if(data.sucesso) {
            form.reset();
            document.getElementById('arquivo').disabled = false;
            document.getElementById('arquivo').setAttribute('required', 'required');
            carregarSolicitacoes();
            carregarCota();
        }
    });
});

document.getElementById('solicitar_balcao').addEventListener('change', function () {
    const upload = document.getElementById('arquivo');
    if (this.checked) {
        upload.disabled = true;
        upload.removeAttribute('required');
    } else {
        upload.disabled = false;
        upload.setAttribute('required', 'required');
    }
});


let ultimosStatus = {};
// Carregar solicitações recentes via AJAX
function carregarSolicitacoes(notify = false) {
    fetch('listar_solicitacoes.php')
        .then(r => r.json())
        .then(data => {
            let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo / Tipo</th><th>Cópias</th><th>Status</th><th>Data</th></tr></thead><tbody>';
            if(data.length === 0) {
                html += '<tr><td colspan="4">Nenhuma solicitação recente.</td></tr>';
            } else {
                data.forEach(s => {
                    // --- MUDANÇA CRÍTICA APLICADA AQUI ---
                    let nomeArquivoExibido;
                    if (!s.arquivo) { // Se 'arquivo' é nulo ou vazio
                        nomeArquivoExibido = '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                    } else {
                        // Cria o link seguro para download
                        nomeArquivoExibido = `<a href="download.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>`;
                    }
                    // ------------------------------------

                    html += `<tr>
                        <td>${nomeArquivoExibido}</td>
                        <td>${s.qtd_copias}</td>
                        <td>${s.status}</td>
                        <td>${new Date(s.data).toLocaleString('pt-BR')}</td>
                    </tr>`;
                });
            }
            html += '</tbody></table>';
            document.getElementById('tabela-solicitacoes').innerHTML = html;
            
            // ... (Sua lógica de notificação continua aqui) ...
        });
}
carregarSolicitacoes();
setInterval(() => carregarSolicitacoes(true), 10000);
</script>
<?php require_once '../../includes/footer.php'; ?>
