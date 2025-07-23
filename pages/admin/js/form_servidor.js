document.addEventListener('DOMContentLoaded', () => {
    const isAdminSelect = document.getElementById('is_admin');
    const setorAdminSelect = document.getElementById('setor_admin');
    const nenhumOption = setorAdminSelect.querySelector('option[value="NENHUM"]');

    function toggleSetorAdmin() {
        if (isAdminSelect.value === '0') {
            // Se NÃO é admin: força "Nenhum", desativa o campo e garante que "Nenhum" seja uma opção válida
            setorAdminSelect.value = 'NENHUM';
            setorAdminSelect.disabled = true;
            if (nenhumOption) nenhumOption.disabled = false;
        } else {
            // Se É admin: ativa o campo, desativa a opção "Nenhum"
            setorAdminSelect.disabled = false;
            if (nenhumOption) nenhumOption.disabled = true;
            // Se "Nenhum" estava selecionado, força o usuário a escolher um setor válido
            if (setorAdminSelect.value === 'NENHUM') {
                setorAdminSelect.value = ''; // Limpa a seleção
            }
        }
    }

    // Executa a função quando a página carrega para definir o estado inicial
    toggleSetorAdmin();

    // Adiciona o ouvinte de evento para futuras mudanças
    isAdminSelect.addEventListener('change', toggleSetorAdmin);
});