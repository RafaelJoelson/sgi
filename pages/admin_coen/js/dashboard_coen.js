document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.querySelector('.dashboard-layout');

    // --- LÓGICA PARA TOAST DE NOTIFICAÇÃO ---
    function showOnPageToast(message) {
        const container = document.getElementById('toast-notification-container');
        if (!container) {
            console.error('Contêiner de toast não encontrado.');
            return;
        }
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (container.contains(toast)) {
                    container.removeChild(toast);
                }
            }, 500);
        }, 5000);
    }

    // Exibir mensagem de sucesso, se existir
    const toastMensagem = document.getElementById('toast-mensagem');
    if (toastMensagem && toastMensagem.textContent.trim()) {
        showOnPageToast(toastMensagem.textContent);
        toastMensagem.style.display = 'none';
    }

    // --- VALIDAÇÃO DO FORMULÁRIO DE BUSCA ---
    const formBusca = document.querySelector('.form-busca');
    if (formBusca) {
        formBusca.addEventListener('submit', (e) => {
            const tipoBusca = formBusca.querySelector('select[name="tipo_busca"]').value;
            const valorBusca = formBusca.querySelector('input[name="valor_busca"]').value.trim();
            if (!tipoBusca || !valorBusca) {
                e.preventDefault();
                showOnPageToast('Por favor, selecione o tipo de busca e insira um valor.');
                return;
            }
            if (tipoBusca === 'cpf' && !/^\d{11}$/.test(valorBusca)) {
                e.preventDefault();
                showOnPageToast('O CPF deve conter 11 dígitos numéricos.');
                return;
            }
            if (tipoBusca === 'siape' && !/^\d{7}$/.test(valorBusca)) {
                e.preventDefault();
                showOnPageToast('O SIAPE deve conter 7 dígitos numéricos.');
                return;
            }
        });
    }

    // --- LÓGICA DOS MODAIS ---
    mainContent.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-redefinir, .btn-excluir-servidor, .modal .close, .btn-cancelar-exclusao');
        if (!target) return;

        e.preventDefault();

        if (target.classList.contains('btn-redefinir')) {
            const siape = target.dataset.siape;
            document.getElementById('siape-modal-servidor').value = siape;
            document.getElementById('modal-redefinir-servidor').style.display = 'block';
        }

        if (target.classList.contains('btn-excluir-servidor')) {
            const nome = target.dataset.nome;
            const url = target.dataset.url; // Pega a URL correta do atributo data-url
            document.getElementById('nome-item-excluir').textContent = nome;
            document.getElementById('btn-confirmar-exclusao').href = url; // Define o link do botão de confirmação
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