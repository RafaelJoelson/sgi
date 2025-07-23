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

});