<?php
require_once '../../includes/config.php';
session_start();

// Verifica permissão
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Dados simulados para os cards (você pode substituir por queries reais)
$total_turmas = 5;
$total_alunos = 120;

// Busca de alunos
$alunos = [];
if (!empty($_GET['tipo_busca']) && !empty($_GET['valor_busca'])) {
    $tipo = $_GET['tipo_busca'];
    $valor = trim($_GET['valor_busca']);

    if ($tipo === 'cpf') {
        $stmt = $conn->prepare("SELECT * FROM Aluno WHERE cpf = :valor");
    } else {
        $stmt = $conn->prepare("SELECT * FROM Aluno WHERE matricula = :valor");
    }

    $stmt->execute([':valor' => $valor]);
    $alunos = $stmt->fetchAll();
} else {
    $sql = "SELECT * FROM Aluno";
    $params = [];

    if (!empty($_GET['turma'])) {
        $sql .= " WHERE turma = :turma";
        $params[':turma'] = $_GET['turma'];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll();
}

include_once '../../includes/header.php';
?>

<main class="container">
<div class="dashboard-container">
<aside>
  <h1>Dashboard - Admin CAD</h1>

  <!-- Cards -->
  <section class="dashboard-cards">
    <div class="card">Turmas Ativas: <?= $total_turmas ?></div>
    <div class="card">Alunos Ativos: <?= $total_alunos ?></div>
  </section>
  <section>
    <button>Cadastrar novo aluno</button>
    <button>Gerar relatório de cotas</button>
  </section>
  
</aside>
  <!-- Tabela de alunos -->
<div class="responsive-table">
    <!-- Busca por CPF ou matrícula -->
  <form method="GET" class="busca-form">
    <label for="tipo_busca">Buscar por:</label>
    <select name="tipo_busca" id="tipo_busca" required>
      <option value="cpf" <?= isset($_GET['tipo_busca']) && $_GET['tipo_busca'] === 'cpf' ? 'selected' : '' ?>>CPF</option>
      <option value="matricula" <?= isset($_GET['tipo_busca']) && $_GET['tipo_busca'] === 'matricula' ? 'selected' : '' ?>>Matrícula</option>
    </select>

    <input type="text" name="valor_busca" placeholder="Digite o CPF ou matrícula" value="<?= htmlspecialchars($_GET['valor_busca'] ?? '') ?>" required>

    <button type="submit">Buscar</button>
  </form>

  <!-- Filtro por turma -->
  <form method="GET" class="filter-form">
    <select name="turma">
      <option value="">Todas as turmas</option>
      <option value="LETRAS 2025/1" <?= (isset($_GET['turma']) && $_GET['turma'] === 'LETRAS 2025/1') ? 'selected' : '' ?>>LETRAS 2025/1</option>
      <option value="LETRAS 2023/1" <?= (isset($_GET['turma']) && $_GET['turma'] === 'LETRAS 2023/1') ? 'selected' : '' ?>>LETRAS 2023/1</option>
    </select>
    <button type="submit">Filtrar</button>
  </form>
    <table>
      <thead>
        <tr>
          <th>Matrícula</th>
          <th>Nome</th>
          <th>Cargo</th>
          <th>Turma</th>
          <th>Período</th>
          <th>Validade</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($alunos as $aluno): ?>
        <tr>
          <td data-label="Matrícula"><?= $aluno->matricula ?></td>
          <td data-label="Nome"><?= $aluno->nome ?></td>
          <td data-label="Cargo"><?= $aluno->cargo ?></td>
          <td data-label="Turma"><?= $aluno->turma ?></td>
          <td data-label="Período"><?= $aluno->periodo ?></td>
          <td data-label="Validade"><?= date('d/m/Y', strtotime($aluno->data_fim_validade)) ?></td>
          <td data-label="Ações">
            <div class="action-buttons">
              <a href="form_aluno.php?matricula=<?= $aluno->matricula ?>">Editar</a>
              <a href="renovar.php?matricula=<?= $aluno->matricula ?>">Renovar</a>
              <a href="redefinir_senha.php?matricula=<?= $aluno->matricula ?>">Senha</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</main>

<?php include_once '../../includes/footer.php'; ?>
