<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Cadastro ou edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $turma = strtoupper(trim($_POST['turma']));
    $turma_nome = trim($_POST['turma_nome']);
    $periodo = trim($_POST['periodo']);

    if ($id) {
        // Atualiza
        $stmt = $conn->prepare("UPDATE CotaAluno SET turma = :turma, turma_nome = :nome, periodo = :periodo WHERE id = :id");
        $stmt->execute([
            ':turma' => $turma,
            ':nome' => $turma_nome,
            ':periodo' => $periodo,
            ':id' => $id
        ]);
        $_SESSION['mensagem'] = "Turma atualizada com sucesso!";
    } else {
        // Insere
        $stmt = $conn->prepare("INSERT INTO CotaAluno (turma, turma_nome, periodo) VALUES (:turma, :nome, :periodo)");
        $stmt->execute([
            ':turma' => $turma,
            ':nome' => $turma_nome,
            ':periodo' => $periodo
        ]);
        $_SESSION['mensagem'] = "Turma cadastrada com sucesso!";
    }

    header('Location: gerenciar_turmas.php');
    exit;
}

// Exclusão
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM CotaAluno WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $_SESSION['mensagem'] = "Turma excluída com sucesso!";
    header('Location: gerenciar_turmas.php');
    exit;
}

// Filtros
$filtro_nome = trim($_GET['nome'] ?? '');
$filtro_periodo = trim($_GET['periodo'] ?? '');

$sql = "SELECT * FROM CotaAluno";
$condicoes = [];
$params = [];

if ($filtro_nome !== '') {
    $condicoes[] = "turma_nome LIKE :nome";
    $params[':nome'] = "%$filtro_nome%";
}
if ($filtro_periodo !== '') {
    $condicoes[] = "periodo = :periodo";
    $params[':periodo'] = $filtro_periodo;
}

if ($condicoes) {
    $sql .= " WHERE " . implode(' AND ', $condicoes);
}
$sql .= " ORDER BY periodo DESC, turma ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$turmas = $stmt->fetchAll();

// Edição
$modo_edicao = false;
$turma_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM CotaAluno WHERE id = :id");
    $stmt->execute([':id' => $_GET['editar']]);
    $turma_editar = $stmt->fetch();
    $modo_edicao = true;
}

include_once '../../includes/header.php';
?>

<main class="container">
  <div class="dashboard-container">
    <aside>
      <h1><?= $modo_edicao ? 'Editar Turma' : 'Nova Turma' ?></h1>

      <?php if (!empty($_SESSION['mensagem'])): ?>
        <div class="mensagem-sucesso"><?= htmlspecialchars($_SESSION['mensagem']) ?></div>
        <?php unset($_SESSION['mensagem']); ?>
      <?php endif; ?>

      <form method="POST" class="form-cotas">
        <?php if ($modo_edicao): ?>
          <input type="hidden" name="id" value="<?= $turma_editar->id ?>">
        <?php endif; ?>

        <label>Sigla da Turma
          <input type="text" name="turma" required value="<?= $turma_editar->turma ?? '' ?>">
        </label>

        <label>Nome do Curso
          <input type="text" name="turma_nome" required value="<?= $turma_editar->turma_nome ?? '' ?>">
        </label>

        <label>Período (ex: 2025.2)
          <input type="text" name="periodo" pattern="^\d{4}\.[12]$" required value="<?= $turma_editar->periodo ?? '' ?>">
        </label>

        <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Turma' ?></button>
        <?php if ($modo_edicao): ?>
          <a href="gerenciar_turmas.php" class="btn-cancelar">Cancelar</a>
        <?php endif; ?>
      </form>
    </aside>

    <div class="responsive-table">
      <!-- Filtros -->
      <form method="GET" class="filter-form">
        <input type="text" name="nome" placeholder="Buscar por nome..." value="<?= htmlspecialchars($filtro_nome) ?>">
        <input type="text" name="periodo" placeholder="Período (ex: 2025.1)" value="<?= htmlspecialchars($filtro_periodo) ?>">
        <button type="submit">Filtrar</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>Sigla</th>
            <th>Nome da Turma</th>
            <th>Período</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($turmas as $t): ?>
            <tr>
              <td data-label="Sigla"><?= $t->turma ?></td>
              <td data-label="Nome"><?= $t->turma_nome ?></td>
              <td data-label="Período"><?= $t->periodo ?></td>
              <td data-label="Ações">
                <a href="?editar=<?= $t->id ?>">Editar</a>
                <a href="?excluir=<?= $t->id ?>" onclick="return confirm('Tem certeza que deseja excluir esta turma?')">Excluir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include_once '../../includes/footer.php'; ?>
