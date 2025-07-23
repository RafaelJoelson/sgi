document.addEventListener('DOMContentLoaded', () => {
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
        // MUDANÇA: Seletor simplificado, removido #btn-gerenciar-servidores
        const target = e.target.closest('button.btn-action, a.btn-action, .close, .btn-cancelar-exclusao');
        if (!target) return;

        if (target.tagName === 'BUTTON' || target.classList.contains('close') || target.classList.contains('btn-cancelar-exclusao')) {
            e.preventDefault();
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
            
            // A lógica agora só precisa de lidar com o tipo 'aluno'
            if (tipo === 'aluno') {
                document.getElementById('nome-item-excluir').textContent = `o aluno ${nome}`;
                document.getElementById('btn-confirmar-exclusao').href = `./functions/excluir_aluno.php?matricula=${id}`;
                document.getElementById('modal-excluir').style.display = 'block';
            }
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