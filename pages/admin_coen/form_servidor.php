<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN' || empty($_SESSION['usuario']['is_admin'])) {
    header('Location: ../../index.php');
    exit;
}

// Verifica se é edição e busca os dados do servidor
$modo_edicao = isset($_GET['siape']) && !empty($_GET['siape']);
$servidor = null;

if ($modo_edicao) {
    $stmt = $conn->prepare(
        "SELECT u.*, s.siape, s.is_admin, s.setor_admin 
         FROM Usuario u JOIN Servidor s ON u.id = s.usuario_id 
         WHERE s.siape = :siape AND s.is_super_admin = 0"
    );
    $stmt->execute([':siape' => $_GET['siape']]);
    $servidor = $stmt->fetch();
    if (!$servidor) {
        $_SESSION['mensagem_erro'] = 'Servidor não encontrado ou não pode ser editado.';
        header('Location: dashboard_coen.php');
        exit;
    }
}

// Buscar semestre letivo vigente para preencher a data de validade
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT data_fim FROM SemestreLetivo WHERE :hoje BETWEEN data_inicio AND data_fim ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();
$data_validade_padrao = $servidor->data_fim_validade ?? ($semestre_vigente->data_fim ?? '');

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="../../css/form_aluno_servidor.css?v=<?= ASSET_VERSION ?>">
<main>
<div class="container-principal">
    <?php if (isset($_SESSION['usuario'])) { gerar_migalhas(); } ?>
    <h1><?= $modo_edicao ? 'Editar Servidor' : 'Cadastrar Novo Servidor' ?></h1>

    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
        <div class="aluno-mensagem-sucesso" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?></div>
        <?php unset($_SESSION['mensagem_sucesso']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['mensagem_erro'])): ?>
        <div class="aluno-mensagem-erro" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_erro']) ?></div>
        <?php unset($_SESSION['mensagem_erro']); ?>
    <?php endif; ?>

    <form action="<?= $modo_edicao ? './functions/processar_edicao_servidor.php' : './functions/processar_cadastro_servidor.php' ?>" method="POST" class="form-aluno">
        <?php if ($modo_edicao): ?>
            <input type="hidden" name="siape" value="<?= htmlspecialchars($servidor->siape) ?>">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <label>*SIAPE
            <input type="text" name="siape" placeholder="Somente números" maxlength="7" pattern="\d{7}" title="Digite os 7 números do SIAPE" required value="<?= $servidor->siape ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>
        <label>*Nome
            <input type="text" name="nome" required value="<?= $servidor->nome ?? '' ?>">
        </label>
        <label>*Sobrenome
            <input type="text" name="sobrenome" required value="<?= $servidor->sobrenome ?? '' ?>">
        </label>
        <label>*E-mail
            <input type="email" name="email" placeholder="email@mail.com" required value="<?= $servidor->email ?? '' ?>">
        </label>
        <label>*CPF
            <input type="text" name="cpf" required placeholder="Somente números" maxlength="11" pattern="\d{11}" title="Digite os 11 números do CPF" value="<?= $servidor->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>
        <?php if (!$modo_edicao): ?>
            <label>*Senha
                <input type="password" name="senha" required minlength="6">
            </label>
        <?php endif; ?>
        
        <label>*É Administrador?
            <select name="is_admin" id="is_admin" required>
                <option value="0" <?= (isset($servidor) && !$servidor->is_admin) ? 'selected' : '' ?>>Não</option>
                <option value="1" <?= (isset($servidor) && $servidor->is_admin) ? 'selected' : '' ?>>Sim</option>
            </select>
        </label>

        <label>*Setor Administrativo
            <select name="setor_admin" id="setor_admin" required>
                <option value="NENHUM" <?= (isset($servidor) && $servidor->setor_admin === 'NENHUM') ? 'selected' : '' ?>>Nenhum</option>
                <option value="CAD" <?= (isset($servidor) && $servidor->setor_admin === 'CAD') ? 'selected' : '' ?>>CAD</option>
                <option value="COEN" <?= (isset($servidor) && $servidor->setor_admin === 'COEN') ? 'selected' : '' ?>>COEN</option>
            </select>
        </label>

        <label>Data Fim da Validade
            <input type="date" name="data_fim_validade" value="<?= htmlspecialchars($data_validade_padrao) ?>" readonly>
        </label>

        <?php if ($modo_edicao): ?>
            <label class="checkbox-label">
                <input type="checkbox" name="ativo" value="1" <?= (isset($servidor) && $servidor->ativo) ? 'checked' : '' ?>>
                Servidor Ativo
            </label>
        <?php endif; ?>

        <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Servidor' ?></button>
    </form>
    <a class="btn-back" href="dashboard_coen.php">&larr; Voltar</a>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAdminSelect = document.getElementById('is_admin');
    const setorAdminSelect = document.getElementById('setor_admin');
    function toggleSetor() {
        setorAdminSelect.disabled = isAdminSelect.value !== '1';
        if (isAdminSelect.value !== '1') setorAdminSelect.value = 'NENHUM';
    }
    isAdminSelect.addEventListener('change', toggleSetor);
    toggleSetor();
});
</script>
<?php include_once '../../includes/footer.php'; ?>