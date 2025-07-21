document.addEventListener('DOMContentLoaded', () => {
    const siapeLogado = '<?= htmlspecialchars($siape_logado) ?>';
    const mainContent = document.querySelector('.dashboard-layout');

    // Lógica para exibir o toast de notificação
    const toast = document.getElementById('toast-mensagem');
    if (toast) {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if(toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 600);
        }, 4000);
    }

    // --- LÓGICA DE DELEGAÇÃO DE EVENTOS ---
    mainContent.addEventListener('click', function(e) {
        const target = e.target.closest('button.btn-action, a.btn-action, .close, .btn-cancelar-exclusao, #btn-gerenciar-servidores');
        if (!target) return;

        // Só previne o default para botões e ações modais, não para links de edição
        if (
            !(target.tagName === 'A' && target.classList.contains('btn-edit')) &&
            !(target.tagName === 'A' && target.classList.contains('btn-action') && target.href && target.closest('#tabela-servidores-cad'))
        ) {
            e.preventDefault();
        }

        if (target.id === 'btn-gerenciar-servidores') {
            document.getElementById('modal-servidores').style.display = 'block';
            carregarServidoresCAD();
        }

        if (target.classList.contains('btn-redefinir')) {
            const matricula = target.dataset.id;
            document.getElementById('matricula-modal-aluno').value = matricula;
            document.getElementById('modal-redefinir-aluno').style.display = 'block';
        }

        if (target.classList.contains('btn-excluir')) {
            const id = target.dataset.id;
            const nome = target.dataset.nome;
            const tipo = target.dataset.tipo;
            
            document.getElementById('nome-item-excluir').textContent = `o ${tipo} ${nome}`;
            const linkConfirmar = (tipo === 'aluno') 
                ? `excluir_aluno.php?matricula=${id}` 
                : `../admin/excluir_servidor.php?siape=${id}`;
            document.getElementById('btn-confirmar-exclusao').href = linkConfirmar;
            document.getElementById('modal-excluir').style.display = 'block';
        }

        if (target.classList.contains('close') || target.classList.contains('btn-cancelar-exclusao')) {
            target.closest('.modal').style.display = 'none';
        }
    });

    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    // --- FUNÇÃO PARA CARREGAR SERVIDORES ---
    function carregarServidoresCAD() {
        fetch('../admin/listar_servidores_cad.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table style="width:100%;"><thead><tr><th>SIAPE</th><th>Nome</th><th>Email</th><th>Ações</th></tr></thead><tbody>';
                if (data.length === 0) {
                    html += '<tr><td colspan="4">Nenhum servidor CAD encontrado.</td></tr>';
                } else {
                    data.forEach(s => {
                        let botaoExcluir = '';
                        // CORREÇÃO: Lógica de autoexclusão e proteção de super admin
                        if (s.siape !== siapeLogado && !(s.is_super_admin == 1)) {
                            botaoExcluir = `<button type="button" class="btn-action btn-delete btn-excluir" 
                                                data-id="${s.siape}" 
                                                data-nome="${s.nome} ${s.sobrenome}" 
                                                data-tipo="servidor"
                                                title="Excluir Servidor">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>`;
                        }
                        html += `<tr>
                            <td>${s.siape}</td>
                            <td>${s.nome} ${s.sobrenome}</td>
                            <td>${s.email}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="../admin/form_servidor.php?siape=${s.siape}" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                    ${botaoExcluir}
                                </div>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table>';
                document.getElementById('tabela-servidores-cad').innerHTML = html;
            });
    }
});