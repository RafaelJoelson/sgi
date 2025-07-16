<?php
// Dashboard do Servidor
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_servidor.css">
<main class="container">
    <h5>Servidor(a): <?= htmlspecialchars($_SESSION['usuario']['nome'] . (isset($_SESSION['usuario']['sobrenome']) ? ' ' . $_SESSION['usuario']['sobrenome'] : '')) ?></h5>
    <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
    
    <form id="form-solicitacao" enctype="multipart/form-data">
        <!-- Seu formulário HTML permanece o mesmo -->
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
        <label>Tipo de impressão
            <select name="tipo_impressao" id="tipo_impressao" required>
                <option value="pb">Preto e Branco</option>
                <option value="colorida">Colorida</option>
            </select>
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
// (Restante do seu JavaScript para carregar cota, enviar formulário, etc. permanece aqui...)
function carregarCota() {
    fetch('cota_servidor.php')
        .then(r => r.json())
        .then(data => {
            if(data.sucesso) {
                document.getElementById('cota-info').innerText = `Cota PB: ${data.cota_pb_disponivel} páginas | Cota Colorida: ${data.cota_color_disponivel} páginas`;
            } else {
                document.getElementById('cota-info').innerText = 'Não foi possível obter as cotas.';
            }
        });
}
carregarCota();

document.getElementById('solicitar_balcao').addEventListener('change', function () {
    const uploadInput = document.getElementById('arquivo');
    if (this.checked) {
        uploadInput.disabled = true;
        uploadInput.removeAttribute('required');
    } else {
        uploadInput.disabled = false;
        uploadInput.setAttribute('required', 'required');
    }
});

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
        if (data.sucesso) {
            form.reset();
            document.getElementById('arquivo').disabled = false;
            document.getElementById('arquivo').setAttribute('required', 'required');
            carregarSolicitacoes();
            carregarCota();
        }
    });
});


let ultimosStatus = {};
function carregarSolicitacoes(notify = false) {
    fetch('listar_solicitacoes.php')
        .then(r => r.json())
        .then(data => {
            let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo / Tipo</th><th>Cópias</th><th>Páginas</th><th>Tipo</th><th>Status</th><th>Data</th></tr></thead><tbody>';
            if(data.length === 0) {
                html += '<tr><td colspan="6">Nenhuma solicitação recente.</td></tr>';
            } else {
                data.forEach(s => {
                    // --- MUDANÇA CRÍTICA APLICADA AQUI ---
                    let nomeArquivoExibido;
                    if (!s.arquivo) {
                        nomeArquivoExibido = '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                    } else {
                        nomeArquivoExibido = `<a href="download.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>`;
                    }
                    // ------------------------------------

                    html += `<tr>
                        <td>${nomeArquivoExibido}</td>
                        <td>${s.qtd_copias}</td>
                        <td>${s.qtd_paginas}</td>
                        <td>${s.colorida == 1 ? 'Colorida' : 'PB'}</td>
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
