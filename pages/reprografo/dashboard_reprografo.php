<?php
// Dashboard do Reprográfo
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    header('Location: ../../index.php');
    exit;
}

// Limpa arquivos da pasta uploads com mais de 15 dias
// NOTA: Esta é uma boa rotina de manutenção. O ideal é sincronizá-la com o evento do banco de dados que limpa as solicitações.
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
                unlink($caminho);
            }
        }
    }
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_reprografo.css">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <h2>Menu</h2>
        <nav class="dashboard-menu">
            <a href="dashboard_reprografo.php" class="dashboard-menu-link active">Solicitações Pendentes</a>
            <a href="relatorio_reprografo.php" class="dashboard-menu-link">Relatórios</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <h1>Painel do Reprográfo</h1>
        <section id="solicitacoes-pendentes">
            <h2>Solicitações Pendentes</h2>
            <div id="tabela-solicitacoes" class="table-responsive"></div>
        </section>
    </main>
</div>
<script>
let ultimosIds = [];

function carregarSolicitacoes(notify = false) {
    fetch('listar_solicitacoes_pendentes.php')
        .then(r => r.json())
        .then(data => {
            let html = '<table class="table table-striped table-hover"><thead><tr><th>Arquivo / Tipo</th><th>Solicitante</th><th>Cópias</th><th>Páginas</th><th>Colorida</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead><tbody>';
            
            if (data.length === 0) {
                html += '<tr><td colspan="8" class="text-center">Nenhuma solicitação pendente.</td></tr>';
            } else {
                data.forEach(s => {
                    // --- MUDANÇA CRÍTICA: Lógica de exibição e link seguro ---
                    let linkArquivo;
                    // Se o campo 'arquivo' for nulo ou vazio, é uma solicitação de balcão.
                    if (!s.arquivo) {
                        linkArquivo = '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                    } else {
                        // Caso contrário, cria o link seguro para download.
                        linkArquivo = `<a href="download_arquivo.php?id=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>`;
                    }
                    // --------------------------------------------------------

                    html += `<tr>
                        <td>${linkArquivo}</td>
                        <td>${s.nome_solicitante}</td>
                        <td>${s.qtd_copias}</td>
                        <td><input type="number" class="form-control form-control-sm" style="width: 70px;" min="1" max="500" value="${s.qtd_paginas}" onchange="editarPaginas(${s.id}, this.value)"></td>
                        <td><span class="badge ${s.colorida == 1 ? 'badge-info' : 'badge-secondary'}">${s.colorida == 1 ? 'Sim' : 'Não'}</span></td>
                        <td>${s.status}</td>
                        <td>${new Date(s.data).toLocaleString('pt-BR')}</td>
                        <td>
                            <button class="btn btn-success btn-sm" onclick="atualizarStatus(${s.id},'Aceita')" title="Aceitar Solicitação">Aceitar<i class="fas fa-check"></i></button>
                            <button class="btn btn-danger btn-sm" onclick="atualizarStatus(${s.id},'Rejeitada')" title="Rejeitar Solicitação">Rejeitar<i class="fas fa-times"></i></button>
                        </td>
                    </tr>`;
                });
            }
            html += '</tbody></table>';
            document.getElementById('tabela-solicitacoes').innerHTML = html;

            // ... (Lógica de notificação continua a mesma) ...
            const ids = data.map(s => s.id);
            if (notify && ultimosIds.length > 0) {
                const novos = ids.filter(id => !ultimosIds.includes(id));
                if (novos.length > 0) {
                    // (código de notificação)
                }
            }
            ultimosIds = ids;
        });
}

carregarSolicitacoes();
setInterval(() => carregarSolicitacoes(true), 10000); // Checa a cada 10s

function atualizarStatus(id, status) {
    // NOTA: O uso de confirm() é funcional, mas para uma melhor UX,
    // considere usar uma biblioteca de modais como SweetAlert ou Bootstrap Modal.
    if (!confirm(`Tem certeza que deseja "${status}" esta solicitação?`)) return;
    
    fetch('atualizar_status_solicitacao.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&status=${status}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.mensagem); // Também pode ser substituído por um toast/notificação.
        if (data.sucesso) carregarSolicitacoes();
    })
    .catch(() => alert('Erro ao atualizar status.'));
}

function editarPaginas(id, valor) {
    fetch('editar_paginas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&qtd_paginas=${valor}`
    })
    .then(r => r.json())
    .then(data => {
        if (!data.sucesso) alert(data.mensagem);
        // Não precisa recarregar a tabela inteira, a mudança já está na tela.
    })
    .catch(() => alert('Erro ao atualizar número de páginas.'));
}
</script>
<?php require_once '../../includes/footer.php'; ?>
