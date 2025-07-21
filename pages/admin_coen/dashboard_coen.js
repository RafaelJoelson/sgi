document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.querySelector('.dashboard-layout');

    // Lógica para exibir o toast de notificação
    const toast = document.getElementById('toast-mensagem');
    if (toast) {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if(toast.parentNode) toast.parentNode.removeChild(toast);
            }, 600);
        }, 4000);
    }

    // Delegação de eventos para todos os botões e modais
    mainContent.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-redefinir, .btn-excluir-servidor, .modal .close, .btn-cancelar-exclusao');
        if (!target) return;

        e.preventDefault();

        if (target.classList.contains('btn-redefinir')) {
            const siape = target.dataset.siape;
            document.getElementById('siape-modal').value = siape;
            document.getElementById('modal-redefinir').style.display = 'block';
        }

        if (target.classList.contains('btn-excluir-servidor')) {
            const siape = target.dataset.siape;
            const nome = target.dataset.nome;
            document.getElementById('nome-servidor-excluir').textContent = nome;
            document.getElementById('btn-confirmar-exclusao').href = `../admin/excluir_servidor.php?siape=${siape}`;
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