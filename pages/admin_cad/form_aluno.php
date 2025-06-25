<?php
require_once '../../includes/config.php';
session_start();

// Verifica permissão
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Verifica se é edição
$modo_edicao = isset($_GET['matricula']);
$aluno = null;

if ($modo_edicao) {
    $stmt = $conn->prepare("SELECT * FROM Aluno WHERE matricula = :matricula");
    $stmt->bindParam(':matricula', $_GET['matricula']);
    $stmt->execute();
    $aluno = $stmt->fetch();
}
?>

<?php include_once '../../includes/header.php'; ?>

<main class="container">
  <h1><?= $modo_edicao ? 'Editar Aluno' : 'Cadastrar Novo Aluno' ?></h1>

  <form action="<?= $modo_edicao ? 'processar_edicao.php' : 'processar_cadastro.php' ?>" method="POST" class="form-aluno">
    <?php if ($modo_edicao): ?>
      <input type="hidden" name="matricula" value="<?= htmlspecialchars($aluno->matricula) ?>">
    <?php endif; ?>

    <label>Matrícula
      <input type="text" name="matricula" required value="<?= $aluno->matricula ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
    </label>

    <label>Nome
      <input type="text" name="nome" required value="<?= $aluno->nome ?? '' ?>">
    </label>

    <label>Email
      <input type="email" name="email" required value="<?= $aluno->email ?? '' ?>">
    </label>

    <label>CPF
      <input type="text" name="cpf" required value="<?= $aluno->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
    </label>

    <?php if (!$modo_edicao): ?>
      <label>Senha
        <input type="password" name="senha" required>
      </label>
    <?php endif; ?>

    <label>Cargo
      <select name="cargo" required>
        <option value="Líder" <?= isset($aluno) && $aluno->cargo === 'Líder' ? 'selected' : '' ?>>Líder</option>
        <option value="Vice-líder" <?= isset($aluno) && $aluno->cargo === 'Vice-líder' ? 'selected' : '' ?>>Vice-líder</option>
      </select>
    </label>

    <label>Turma
      <input type="text" name="turma" required value="<?= $aluno->turma ?? '' ?>">
    </label>

    <label>Período
      <input type="text" name="periodo" required value="<?= $aluno->periodo ?? '' ?>">
    </label>

    <label>Data Fim da Validade
      <input type="date" name="data_fim_validade" required value="<?= $aluno->data_fim_validade ?? '' ?>">
    </label>

    <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Aluno' ?></button>
  </form>
</main>

<?php include_once '../../includes/footer.php'; ?>
