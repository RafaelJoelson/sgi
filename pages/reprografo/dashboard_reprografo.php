<?php
session_start();
require_once '../../includes/config.php';

// Permissão: apenas reprografo pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    header('Location: ../../login_repro.php');
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
            <a href="#" id="btn-alterar-dados" class="dashboard-menu-link">Alterar Meus Dados</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <h2>Painel do Reprográfo</h2>
        <section id="solicitacoes-pendentes">
            <h2>Solicitações Pendentes</h2>
            <div id="tabela-solicitacoes" class="table-responsive"></div>
        </section>
    </main>
</div>

<!-- Modal para editar dados do reprografo -->
<div id="modal-editar-dados" class="modal">
    <div class="modal-content">
        <span class="close" id="close-modal-editar">&times;</span>
        <h2>Alterar Meus Dados</h2>
        <form id="form-editar-reprografo">
            <div id="mensagem-modal-erro" class="mensagem-erro" style="display: none;"></div>
            <!-- GARANTIA: O campo oculto para o ID deve estar aqui -->
            <input type="hidden" id="reprografo-id" name="id">
            <label>Login
                <input type="text" id="reprografo-login" name="login" readonly disabled style="background-color: #e9ecef;">
            </label>
            <label>Nome
                <input type="text" id="reprografo-nome" name="nome" required>
            </label>
            <label>Sobrenome
                <input type="text" id="reprografo-sobrenome" name="sobrenome" required>
            </label>
            <label>Email
                <input type="email" id="reprografo-email" name="email">
            </label>
            <hr>
            <p>Deixe os campos de senha em branco para não alterá-la.</p>
            <label>Nova Senha
                <input type="password" id="reprografo-nova-senha" name="nova_senha" minlength="6">
            </label>
            <label>Confirmar Nova Senha
                <input type="password" id="reprografo-confirma-senha" name="confirma_senha" minlength="6">
            </label>
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
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
                        let linkArquivo = s.arquivo ? `<a href="download_arquivo.php?id=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>` : '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                        html += `<tr>
                            <td>${linkArquivo}</td>
                            <td>${s.nome_solicitante}</td>
                            <td>${s.qtd_copias}</td>
                            <td><input type="number" class="form-control form-control-sm" style="width: 70px;" min="1" max="500" value="${s.qtd_paginas}" onchange="editarPaginas(${s.id}, this.value)"></td>
                            <td><span class="badge ${s.colorida == 1 ? 'badge-info' : 'badge-secondary'}">${s.colorida == 1 ? 'Sim' : 'Não'}</span></td>
                            <td>${s.status}</td>
                            <td>${new Date(s.data).toLocaleString('pt-BR')}</td>
                            <td>
                                <button onclick="atualizarStatus(${s.id},'Aceita')">Aceitar</button>
                                <button onclick="atualizarStatus(${s.id},'Rejeitada')">Rejeitar</button>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table>';
                document.getElementById('tabela-solicitacoes').innerHTML = html;
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

    carregarSolicitacoes();
    setInterval(() => carregarSolicitacoes(true), 10000);

    const modalEditar = document.getElementById('modal-editar-dados');
    const formEditar = document.getElementById('form-editar-reprografo');

    document.getElementById('btn-alterar-dados').addEventListener('click', (e) => {
        e.preventDefault();
        fetch('obter_dados_reprografo.php')
            .then(response => {
                if (!response.ok) throw new Error(`Erro de rede: ${response.statusText}`);
                return response.json();
            })
            .then(data => {
                if (data.sucesso && data.dados) {
                    document.getElementById('reprografo-id').value = data.dados.id;
                    document.getElementById('reprografo-login').value = data.dados.login;
                    document.getElementById('reprografo-nome').value = data.dados.nome;
                    document.getElementById('reprografo-sobrenome').value = data.dados.sobrenome;
                    document.getElementById('reprografo-email').value = data.dados.email;
                    modalEditar.style.display = 'block';
                } else {
                    alert('Falha ao obter dados: ' + (data.mensagem || 'Resposta inválida do servidor.'));
                }
            })
            .catch(error => {
                console.error('Erro detalhado ao buscar dados do reprografo:', error);
                alert('Ocorreu um erro de comunicação ao buscar seus dados. Verifique o console para mais detalhes.');
            });
    });

    document.getElementById('close-modal-editar').addEventListener('click', () => {
        modalEditar.style.display = 'none';
    });
    window.addEventListener('click', (e) => {
        if (e.target === modalEditar) {
            modalEditar.style.display = 'none';
        }
    });

    formEditar.addEventListener('submit', function(e) {
        e.preventDefault();
        const novaSenha = document.getElementById('reprografo-nova-senha').value;
        const confirmaSenha = document.getElementById('reprografo-confirma-senha').value;
        const msgErro = document.getElementById('mensagem-modal-erro');

        if (novaSenha !== confirmaSenha) {
            msgErro.textContent = 'As senhas não coincidem.';
            msgErro.style.display = 'block';
            return;
        }
        msgErro.style.display = 'none';

        const formData = new FormData(formEditar);
        fetch('processar_edicao_reprografo.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            alert(data.mensagem);
            if (data.sucesso) {
                modalEditar.style.display = 'none';
                if (data.novo_nome) {
                    const userInfo = document.querySelector('.user-info h3');
                    if (userInfo) {
                        userInfo.textContent = `Bem-vindo: ${data.novo_nome}`;
                    }
                }
            }
        });
    });
});
</script>
<?php require_once '../../includes/footer.php'; ?>
