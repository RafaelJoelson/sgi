<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// Verifica se é edição e busca os dados do servidor
$modo_edicao = isset($_GET['siape']) && !empty($_GET['siape']);
$servidor = null;
$is_super_admin_edit = false; // Flag para verificar se o perfil editado é super admin

if ($modo_edicao) {
    $stmt = $conn->prepare("SELECT * FROM Servidor WHERE siape = :siape");
    $stmt->execute([':siape' => $_GET['siape']]);
    $servidor = $stmt->fetch();
    if (!$servidor) {
        $_SESSION['mensagem_erro'] = 'Servidor não encontrado.';
        header('Location: ../admin_coen/dashboard_coen.php');
        exit;
    }
    // Define a flag se o usuário que está sendo editado for um super admin
    $is_super_admin_edit = !empty($servidor->is_super_admin);
}

// Buscar semestre letivo vigente para preencher a data de validade
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT data_fim FROM SemestreLetivo WHERE :hoje BETWEEN data_inicio AND data_fim ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();
$data_validade_padrao = $servidor->data_fim_validade ?? ($semestre_vigente->data_fim ?? '');

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/form_servidor.css?v=<?= ASSET_VERSION ?>">
<main>
    <div class="container-principal">
        <?php if (isset($_SESSION['usuario'])) { gerar_migalhas(); } ?>
        
        <h1><?= $modo_edicao ? 'Editar Servidor' : 'Cadastrar Novo Servidor' ?></h1>

        <?php if ($modo_edicao && $is_super_admin_edit): ?>
            <div class="mensagem-aviso">
                <i class="fas fa-shield-alt"></i> 
                Este é um Super Administrador. A edição de seus dados e permissões só pode ser feita diretamente no banco de dados pela equipe de TI.
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="mensagem-sucesso" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?></div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="mensagem-erro" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_erro']) ?></div>
            <?php unset($_SESSION['mensagem_erro']); ?>
        <?php endif; ?>

        <form action="<?= $modo_edicao ? 'processar_edicao_servidor.php' : 'processar_cadastro_servidor.php' ?>" method="POST" class="form-servidor">
            <?php if ($modo_edicao): ?>
                <input type="hidden" name="siape" value="<?= htmlspecialchars($servidor->siape) ?>">
            <?php endif; ?>

            <label>*SIAPE
                <input type="text" name="siape" placeholder="Somente números" maxlength="7" pattern="\d{7}" title="Digite os 7 números do SIAPE" required value="<?= $servidor->siape ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
            </label>
            <label>*Nome
                <input type="text" name="nome" required value="<?= $servidor->nome ?? '' ?>" <?= $is_super_admin_edit ? 'disabled' : '' ?>>
            </label>
            <label>*Sobrenome
                <input type="text" name="sobrenome" required value="<?= $servidor->sobrenome ?? '' ?>" <?= $is_super_admin_edit ? 'disabled' : '' ?>>
            </label>
            <label>*E-mail
                <input type="email" name="email" placeholder="email@mail.com" required value="<?= $servidor->email ?? '' ?>" <?= $is_super_admin_edit ? 'disabled' : '' ?>>
            </label>
            <label>*CPF
                <input type="text" name="cpf" required placeholder="Somente números" maxlength="11" pattern="\d{11}" title="Digite os 11 números do CPF" value="<?= $servidor->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
            </label>
            <?php if (!$modo_edicao): ?>
                <label>Senha
                    <input type="password" name="senha" required minlength="6">
                </label>
            <?php endif; ?>
            
            <label>*É Administrador?
                <select name="is_admin" id="is_admin" required <?= $is_super_admin_edit ? 'disabled' : '' ?>>
                    <option value="0" <?= (isset($servidor) && !$servidor->is_admin) ? 'selected' : '' ?>>Não</option>
                    <option value="1" <?= (isset($servidor) && $servidor->is_admin) ? 'selected' : '' ?>>Sim</option>
                </select>
            </label>

            <label>*Setor Administrativo
                <select name="setor_admin" id="setor_admin" required <?= $is_super_admin_edit ? 'disabled' : '' ?>>
                    <option value="" disabled>Selecione um setor</option>
                    <option value="NENHUM" <?= (isset($servidor) && $servidor->setor_admin === 'NENHUM') ? 'selected' : '' ?>>Nenhum</option>
                    <option value="CAD" <?= (isset($servidor) && $servidor->setor_admin === 'CAD') ? 'selected' : '' ?>>CAD</option>
                    <option value="COEN" <?= (isset($servidor) && $servidor->setor_admin === 'COEN') ? 'selected' : '' ?>>COEN</option>
                </select>
            </label>

            <label>Data Fim da Validade
                <input type="date" name="data_fim_validade" value="<?= htmlspecialchars($data_validade_padrao) ?>" <?= $is_super_admin_edit ? 'disabled' : '' ?>>
            </label>

            <?php if ($modo_edicao): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="ativo" value="1" <?= (isset($servidor) && $servidor->ativo) ? 'checked' : '' ?> <?= $is_super_admin_edit ? 'disabled' : '' ?>>
                    Servidor Ativo
                </label>
            <?php endif; ?>

            <button type="submit" <?= $is_super_admin_edit ? 'disabled' : '' ?>><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Servidor' ?></button>
        </form>
        <a class="btn-back" href="javascript:history.back()">&larr; Voltar</a>
    </div>
</main>
<script>
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
</script>
<?php include_once '../../includes/footer.php'; ?>
