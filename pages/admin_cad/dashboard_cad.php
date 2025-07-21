<?php
// Dashboard da Reprografia
session_start();
require_once '../../includes/config.php';
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    header('Location: ../../reprografia.php');
    exit;
}
// Limpa arquivos da pasta uploads com mais de 15 dias
$diretorioUploads = realpath(__DIR__ . '/../../uploads');
if ($diretorioUploads && is_dir($diretorioUploads)) {
    $arquivos = scandir($diretorioUploads);
    $agora = time();
    $dias = 15 * 24 * 60 * 60; // 15 dias em segundos

    foreach ($arquivos as $arquivo) {
        $caminho = $diretorioUploads . DIRECTORY_SEPARATOR . $arquivo;
        if (is_file($caminho)) {
            $modificadoHa = $agora - filemtime($caminho);
            if ($modificadoHa > $dias) {
                @unlink($caminho);
            }
        }
    }
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_reprografia.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside-repro">
        <div class="container-principal">
        <?php
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <nav class="dashboard-menu">
            <img src="../../img/logo_reprografia.png" alt="Logo da Reprografia">
            <a href="dashboard_reprografia.php" class="dashboard-menu-link active">Solicitações Pendentes</a>
            <a href="relatorio_reprografia.php" class="dashboard-menu-link">Relatórios</a>
            <a href="../admin/limpar_uploads.php" class="dashboard-menu-link">Limpar Pasta Uploads</a>
            <a href="#" id="btn-alterar-dados" class="dashboard-menu-link">Alterar Meus Dados</a>
        </nav>
        </div>
    </aside>
    <main class="dashboard-main-repro">
        <div id="toast-notification-container"></div>
        <h2>Painel da Reprografia</h2>
        <section id="solicitacoes-pendentes">
            <div class="section-header">
                <h2>Solicitações Pendentes</h2>
                <button id="btn-ativar-notificacoes" class="btn-notificacao" title="Clique para permitir notificações no navegador">
                    <i class="fas fa-bell"></i> Ativar Notificações
                </button>
            </div>
            <div id="tabela-solicitacoes" class="table-responsive"></div>
        </section>
    </main>
</div>

<!-- Modal para editar dados do reprografo -->
<div id="modal-editar-dados" class="modal">
    <div class="modal-content">
        <span class="close" id="close-modal-editar">&times;</span>
        <h2>Alterar Meus Dados</h2>
        <form id="form-editar-reprografia" enctype="multipart/form-data">
            <div id="mensagem-modal-erro" class="mensagem-erro" style="display: none;"></div>
            <input type="hidden" id="reprografia-id" name="id">
            <label>Logo da Reprografia (PNG ou WEBP)
                <input type="file" id="reprografia-logo" name="logo" accept=".png,.webp,image/png,image/webp">
            </label>
            <label>Login
                <input type="text" id="reprografia-login" name="login" readonly disabled style="background-color: #e9ecef;">
            </label>
            <label>Nome
                <input type="text" id="reprografia-nome" name="nome" required>
            </label>
            <label>Sobrenome
                <input type="text" id="reprografia-sobrenome" name="sobrenome" required>
            </label>
            <label>Email
                <input type="email" id="reprografia-email" name="email">
            </label>
            <hr>
            <p>Deixe os campos de senha em branco para não alterá-la.</p>
            <label>Nova Senha
                <input type="password" id="reprografia-nova-senha" name="nova_senha" minlength="6">
            </label>
            <label>Confirmar Nova Senha
                <input type="password" id="reprografia-confirma-senha" name="confirma_senha" minlength="6">
            </label>
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let ultimosIds = [];
    const originalTitle = document.title;
    let notificationInterval = null;
    let audioContext;

    // --- LÓGICA DE NOTIFICAÇÃO COMPLETA ---
    function playNotificationSound() { /* ...código de som... */ }
    function startTitleFlash(message) { /* ...código de piscar título... */ }
    function stopTitleFlash() { /* ...código para parar de piscar... */ }
    document.addEventListener('visibilitychange', () => { if (!document.hidden) stopTitleFlash(); });

    function showOnPageToast(message) {
        const container = document.getElementById('toast-notification-container');
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `<i class="fas fa-bell"></i> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => { if (container.contains(toast)) container.removeChild(toast); }, 500);
        }, 5000);
    }

    function handleNewNotification(message) {
        showOnPageToast(message);
        if (document.hidden) {
            playNotificationSound();
            startTitleFlash("Nova Solicitação!");
        }
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification("SGI - Reprografia", { body: message, icon: '../../favicon.ico' });
        }
    }

    // --- FUNÇÕES PRINCIPAIS DA PÁGINA ---
    function carregarSolicitacoes(notify = false) {
        fetch('listar_solicitacoes_pendentes.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table class="table table-striped table-hover"><thead><tr><th>Arquivo / Tipo</th><th>Solicitante</th><th>Cópias</th><th>Páginas</th><th>Colorida</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead><tbody>';
                if (data.length === 0) {
                    html += '<tr><td colspan="8" class="text-center">Nenhuma solicitação pendente.</td></tr>';
                } else {
                    data.forEach(s => {
                        let linkArquivo = s.arquivo ? `<a href="download_arquivo.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>` : '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                        html += `<tr>
                            <td>${linkArquivo}</td><td>${s.nome_solicitante}</td><td>${s.qtd_copias}</td>
                            <td><input type="number" class="form-control form-control-sm" style="width: 70px;" value="${s.qtd_paginas}" onchange="editarPaginas(${s.id}, this.value)"></td>
                            <td><span class="badge ${s.colorida == 1 ? 'badge-info' : 'badge-secondary'}">${s.colorida == 1 ? 'Sim' : 'Não'}</span></td>
                            <td>${s.status}</td><td>${s.data}</td>
                            <td class="actions">
                                <button class="btn-accept" onclick="atualizarStatus(${s.id},'Aceita')"><i class="fas fa-check"></i></button>
                                <button class="btn-reject" onclick="atualizarStatus(${s.id},'Rejeitada')"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table>';
                document.getElementById('tabela-solicitacoes').innerHTML = html;

                const idsAtuais = data.map(s => s.id);
                if (notify && ultimosIds.length > 0) {
                    const novasSolicitacoes = idsAtuais.filter(id => !ultimosIds.includes(id));
                    if (novasSolicitacoes.length > 0) {
                        const mensagem = `Você recebeu ${novasSolicitacoes.length} nova(s) solicitação(ões)!`;
                        handleNewNotification(mensagem);
                    }
                }
                ultimosIds = idsAtuais;
            });
    }

    window.atualizarStatus = function(id, status) {
        if (!confirm(`Tem certeza que deseja "${status}" esta solicitação?`)) return;
        fetch('atualizar_status_solicitacao.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&status=${status}`
        }).then(r => r.json()).then(data => {
            alert(data.mensagem);
            if (data.sucesso) carregarSolicitacoes();
        });
    }

    window.editarPaginas = function(id, valor) {
        fetch('editar_paginas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&qtd_paginas=${valor}`
        }).then(r => r.json()).then(data => {
            if (!data.sucesso) alert(data.mensagem);
        });
    }

    const btnAtivarNotificacoes = document.getElementById('btn-ativar-notificacoes');
    if (!("Notification" in window) || Notification.permission !== 'default') {
        btnAtivarNotificacoes.style.display = 'none';
    }
    btnAtivarNotificacoes.addEventListener('click', () => { /* ...código existente... */ });

    carregarSolicitacoes();
    setInterval(() => carregarSolicitacoes(true), 15000);

    // --- LÓGICA DO MODAL DE EDIÇÃO ---
    const modalEditar = document.getElementById('modal-editar-dados');
    const formEditar = document.getElementById('form-editar-reprografia');

    document.getElementById('btn-alterar-dados').addEventListener('click', (e) => {
        e.preventDefault();
        fetch('obter_dados_reprografia.php')
            .then(response => response.json())
            .then(data => {
                if (data.sucesso && data.dados) {
                    document.getElementById('reprografia-id').value = data.dados.id;
                    document.getElementById('reprografia-login').value = data.dados.login;
                    document.getElementById('reprografia-nome').value = data.dados.nome;
                    document.getElementById('reprografia-sobrenome').value = data.dados.sobrenome;
                    document.getElementById('reprografia-email').value = data.dados.email;
                    modalEditar.style.display = 'block';
                } else {
                    alert('Falha ao obter dados: ' + data.mensagem);
                }
            });
    });

    document.getElementById('close-modal-editar').addEventListener('click', () => modalEditar.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === modalEditar) modalEditar.style.display = 'none'; });

    formEditar.addEventListener('submit', function(e) {
        e.preventDefault();
        const novaSenha = document.getElementById('reprografia-nova-senha').value;
        const confirmaSenha = document.getElementById('reprografia-confirma-senha').value;
        const msgErro = document.getElementById('mensagem-modal-erro');

        if (novaSenha !== confirmaSenha) {
            msgErro.textContent = 'As senhas não coincidem.';
            msgErro.style.display = 'block';
            return;
        }
        msgErro.style.display = 'none';

        const formData = new FormData(formEditar);
        fetch('processar_edicao_reprografia.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                modalEditar.style.display = 'none';
                showOnPageToast(data.mensagem); // Usa o toast para sucesso
                if (data.novo_nome) {
                    const userInfo = document.querySelector('.user-info h3');
                    if (userInfo) userInfo.textContent = `Bem-vindo: ${data.novo_nome}`;
                }
            } else {
                msgErro.textContent = data.mensagem;
                msgErro.style.display = 'block';
            }
        });
    });
});
</script>
<?php require_once '../../includes/footer.php'; ?>
