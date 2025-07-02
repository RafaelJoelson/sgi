<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// Verifica se é edição
$modo_edicao = isset($_GET['siap']);
$servidor = null;

if ($modo_edicao) {
    $stmt = $conn->prepare("SELECT * FROM Servidor WHERE siap = :siap");
    $stmt->bindParam(':siap', $_GET['siap']);
    $stmt->execute();
    $servidor = $stmt->fetch();
}

// Buscar semestre letivo vigente para exibir ao usuário (opcional)
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT * FROM SemestreLetivo WHERE data_inicio <= :hoje AND data_fim >= :hoje ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();

?>
<?php include_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="form_servidor.css">
<main>
  <h1><?= $modo_edicao ? 'Editar Servidor' : 'Cadastrar Novo Servidor' ?></h1>

  <!-- Exibe semestre letivo vigente, se encontrado -->
  <?php if ($semestre_vigente): ?>
    <div class="info-semestre">
      Semestre letivo vigente: <b><?= $semestre_vigente->ano ?>/<?= $semestre_vigente->semestre ?></b> (<?= date('d/m/Y', strtotime($semestre_vigente->data_inicio)) ?> a <?= date('d/m/Y', strtotime($semestre_vigente->data_fim)) ?>)
    </div>
  <?php endif; ?>

  <form action="<?= $modo_edicao ? 'processar_edicao_servidor.php' : 'processar_cadastro_servidor.php' ?>" method="POST" class="form-servidor">
    <?php if ($modo_edicao): ?>
      <input type="hidden" name="siap" value="<?= htmlspecialchars($servidor->siap) ?>">
    <?php endif; ?>

    <label>SIAP
      <input type="text" name="siap" required value="<?= $servidor->siap ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
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
      <input type="text" name="cpf" required maxlength="11" value="<?= $servidor->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
    </label>

    <?php if (!$modo_edicao): ?>
      <label>Senha
        <input type="password" name="senha" required>
      </label>
    <?php endif; ?>

    <label>Setor Administrativo
      <select name="setor_admin" required <?= ($_SESSION['usuario']['setor_admin'] === 'CAD' && $_SESSION['usuario']['is_admin']) ? 'disabled' : '' ?>>
        <option value="NENHUM" <?= isset($servidor) && $servidor->setor_admin === 'NENHUM' ? 'selected' : '' ?>>Nenhum</option>
        <option value="CAD" <?= isset($servidor) && $servidor->setor_admin === 'CAD' ? 'selected' : '' ?>>CAD</option>
        <option value="COEN" <?= isset($servidor) && $servidor->setor_admin === 'COEN' ? 'selected' : '' ?>>COEN</option>
      </select>
      <?php if ($_SESSION['usuario']['setor_admin'] === 'CAD' && $_SESSION['usuario']['is_admin']): ?>
        <input type="hidden" name="setor_admin" value="CAD">
      <?php endif; ?>
    </label>

    <label>Administrador?
      <select name="is_admin" required>
        <option value="0" <?= isset($servidor) && !$servidor->is_admin ? 'selected' : '' ?>>Não</option>
        <option value="1" <?= isset($servidor) && $servidor->is_admin ? 'selected' : '' ?>>Sim</option>
      </select>
    </label>

    <label>Data Fim da Validade
      <input type="date" name="data_fim_validade" required value="<?= $semestre_vigente ? $semestre_vigente->data_fim : '' ?>" readonly>
    </label>

    <?php if ($modo_edicao): ?>
      <label style="flex-direction: row; align-items: center; gap: 0.5em;">
        <input type="checkbox" name="ativo" value="1" <?= isset($servidor) && $servidor->ativo ? 'checked' : '' ?>>
        Ativo <span style="font-size:0.95em; color:#555; font-weight:400;">(Desmarque para inativar o servidor)</span>
      </label>
    <?php endif; ?>

    <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Servidor' ?></button>
  </form>
  <a class="btn-back" href="javascript:history.back()">Voltar</a>
</main>
<?php include_once '../../includes/footer.php'; ?>
