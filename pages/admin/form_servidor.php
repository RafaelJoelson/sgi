<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// Verifica se está em modo de edição e busca os dados do servidor
$modo_edicao = isset($_GET['siape']) && !empty($_GET['siape']);
$servidor = null;
if ($modo_edicao) {
    $stmt = $conn->prepare("SELECT * FROM Servidor WHERE siape = :siape");
    $stmt->execute([':siape' => $_GET['siape']]);
    $servidor = $stmt->fetch();
    if (!$servidor) {
        // Se o SIAPE não for encontrado, redireciona com erro
        $_SESSION['mensagem_erro'] = 'Servidor não encontrado.';
        header('Location: ../admin_coen/dashboard_coen.php'); // Ou para o painel de admin apropriado
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
<link rel="stylesheet" href="form_servidor.css?v=<?= ASSET_VERSION ?>">
<main>
    <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
    <h1><?= $modo_edicao ? 'Editar Servidor' : 'Cadastrar Novo Servidor' ?></h1>

    <!-- Exibe mensagens de sucesso ou erro da sessão -->
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

        <label>SIAPE
            <input type="text" name="siape" maxlength="8" required value="<?= $servidor->siape ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>

        <label>Nome
            <input type="text" name="nome" required value="<?= $servidor->nome ?? '' ?>">
        </label>

        <label>Sobrenome
            <input type="text" name="sobrenome" required value="<?= $servidor->sobrenome ?? '' ?>">
        </label>

        <label>Email
            <input type="email" name="email" required value="<?= $servidor->email ?? '' ?>">
        </label>

        <label>CPF
            <input type="text" name="cpf" required maxlength="11" pattern="\d{11}" title="Digite os 11 números do CPF" value="<?= $servidor->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>

        <?php if (!$modo_edicao): ?>
            <label>Senha
                <input type="password" name="senha" required minlength="6">
            </label>
        <?php endif; ?>

        <label>Setor Administrativo
            <select name="setor_admin" required>
                <option value="NENHUM" <?= (isset($servidor) && $servidor->setor_admin === 'NENHUM') ? 'selected' : '' ?>>Nenhum</option>
                <option value="CAD" <?= (isset($servidor) && $servidor->setor_admin === 'CAD') ? 'selected' : '' ?>>CAD</option>
                <option value="COEN" <?= (isset($servidor) && $servidor->setor_admin === 'COEN') ? 'selected' : '' ?>>COEN</option>
            </select>
        </label>

        <label>É Administrador?
            <select name="is_admin" required>
                <option value="0" <?= (isset($servidor) && !$servidor->is_admin) ? 'selected' : '' ?>>Não</option>
                <option value="1" <?= (isset($servidor) && $servidor->is_admin) ? 'selected' : '' ?>>Sim</option>
            </select>
        </label>

        <label>Data Fim da Validade
            <input type="date" name="data_fim_validade" value="<?= htmlspecialchars($data_validade_padrao) ?>">
        </label>

        <?php if ($modo_edicao): ?>
            <label class="checkbox-label">
                <input type="checkbox" name="ativo" value="1" <?= (isset($servidor) && $servidor->ativo) ? 'checked' : '' ?>>
                Servidor Ativo
            </label>
        <?php endif; ?>

        <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Servidor' ?></button>
    </form>
    <a class="btn-back" href="javascript:history.back()">&larr; Voltar</a>
</main>
<?php include_once '../../includes/footer.php'; ?>
