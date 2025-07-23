document.addEventListener('DOMContentLoaded', function() {
    // Lógica para exibir o toast de notificação
    const toast = document.getElementById('toast-mensagem');
    if (toast) {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // Lógica de confirmação do formulário de semestre
    const formSemestre = document.getElementById('form-semestre');
    if (formSemestre) {
        formSemestre.addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja salvar/alterar o semestre letivo? Esta ação impacta datas e cotas institucionais.')) {
                e.preventDefault();
            }
        });
    }

    // Lógica para o Modal de Cotas
    const modalCotas = document.getElementById('modal-cotas');
    const btnAbrirModal = document.getElementById('btn-abrir-modal-cotas');
    const btnFecharModal = document.getElementById('close-modal-cotas');
    const formCotas = document.getElementById('form-cotas');
    const mensagemModal = document.getElementById('mensagem-modal-cotas');

    btnAbrirModal.addEventListener('click', () => {
        fetch('./functions/obter_configuracoes.php')
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    document.getElementById('cota_padrao_aluno').value = data.dados.cota_padrao_aluno || 600;
                    document.getElementById('cota_padrao_servidor_pb').value = data.dados.cota_padrao_servidor_pb || 1000;
                    document.getElementById('cota_padrao_servidor_color').value = data.dados.cota_padrao_servidor_color || 100;
                    modalCotas.style.display = 'block';
                } else {
                    alert('Erro ao carregar configurações: ' + data.mensagem);
                }
            });
    });

    btnFecharModal.addEventListener('click', () => modalCotas.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modalCotas) modalCotas.style.display = 'none';
    });

    // Enviar formulário do modal e recarregar a página em caso de sucesso
    formCotas.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formCotas);

        fetch('./functions/salvar_configuracoes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Recarrega a página para que o PHP possa exibir o toast de sucesso
                window.location.reload();
            } else {
                // Se falhar, mostra o erro dentro do modal sem recarregar
                mensagemModal.textContent = data.mensagem;
                mensagemModal.className = 'mensagem-erro';
                mensagemModal.style.display = 'block';
            }
        });
    });
});