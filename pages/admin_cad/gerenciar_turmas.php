<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// Cadastro ou edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $curso_id = intval($_POST['curso_id']);
    $periodo = trim($_POST['periodo']);

    // Validação do campo período
    $periodoValido = preg_match('/^\d+º Período$/u', $periodo);
    if (!$periodoValido) {
        $_SESSION['mensagem'] = "Formato de período inválido! Use apenas '1º Período', '2º Período', etc.";
        header('Location: gerenciar_turmas.php' . ($id ? '?editar=' . $id : ''));
        exit;
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE Turma SET curso_id = :curso_id, periodo = :periodo WHERE id = :id");
        $stmt->execute([
            ':curso_id' => $curso_id,
            ':periodo' => $periodo,
            ':id' => $id
        ]);
        $_SESSION['mensagem'] = "Turma atualizada com sucesso!";
    } else {
        $stmt = $conn->prepare("INSERT INTO Turma (curso_id, periodo) VALUES (:curso_id, :periodo)");
        $stmt->execute([
            ':curso_id' => $curso_id,
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
    $stmt = $conn->prepare("DELETE FROM Turma WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $_SESSION['mensagem'] = "Turma excluída com sucesso!";
    header('Location: gerenciar_turmas.php');
    exit;
}

// Cadastro de novo curso
if (isset($_POST['novo_curso_nome']) && isset($_POST['novo_curso_sigla'])) {
    $novo_nome = trim($_POST['novo_curso_nome']);
    $nova_sigla = strtoupper(trim($_POST['novo_curso_sigla']));
    if ($novo_nome && $nova_sigla) {
        // Verifica se já existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Curso WHERE nome_completo = :nome OR sigla = :sigla");
        $stmt->execute([':nome' => $novo_nome, ':sigla' => $nova_sigla]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $conn->prepare("INSERT INTO Curso (sigla, nome_completo) VALUES (:sigla, :nome)");
            $stmt->execute([':sigla' => $nova_sigla, ':nome' => $novo_nome]);
            $_SESSION['mensagem'] = "Curso cadastrado com sucesso!";
            header('Location: gerenciar_turmas.php');
            exit;
        } else {
            $_SESSION['mensagem'] = "Já existe um curso com esse nome ou sigla.";
            header('Location: gerenciar_turmas.php');
            exit;
        }
    } else {
        $_SESSION['mensagem'] = "Preencha o nome e a sigla do novo curso.";
        header('Location: gerenciar_turmas.php');
        exit;
    }
}

// Filtros
$filtro_nome = trim($_GET['nome'] ?? '');
$filtro_periodo = trim($_GET['periodo'] ?? '');

$sql = "SELECT t.*, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id";
$condicoes = [];
$params = [];

if ($filtro_nome !== '') {
    $condicoes[] = "c.nome_completo LIKE :nome";
    $params[':nome'] = "%$filtro_nome%";
}
if ($filtro_periodo !== '') {
    $condicoes[] = "t.periodo = :periodo";
    $params[':periodo'] = $filtro_periodo;
}

if ($condicoes) {
    $sql .= " WHERE " . implode(' AND ', $condicoes);
}
$sql .= " ORDER BY t.periodo DESC, c.sigla ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$turmas = $stmt->fetchAll();

// Edição
$modo_edicao = false;
$turma_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT t.*, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id WHERE t.id = :id");
    $stmt->execute([':id' => $_GET['editar']]);
    $turma_editar = $stmt->fetch();
    $modo_edicao = true;
}
// Buscar cursos para o select
$stmtCursos = $conn->query("SELECT id, sigla, nome_completo FROM Curso ORDER BY nome_completo ASC");
$cursos = $stmtCursos->fetchAll();

// Buscar períodos distintos para o select de filtro
$stmtPeriodos = $conn->query("SELECT DISTINCT periodo FROM Turma ORDER BY periodo DESC");
$periodos = $stmtPeriodos->fetchAll(PDO::FETCH_COLUMN);

include_once '../../includes/header.php';
?>

<link rel="stylesheet" href="gerenciar_turmas.css">
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

        <label>Curso
          <select name="curso_id" required>
            <option value="" disabled selected>Selecione o curso</option>
            <?php foreach ($cursos as $curso): ?>
              <option value="<?= $curso->id ?>" <?= ($modo_edicao && $turma_editar->curso_id == $curso->id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($curso->nome_completo . ' (' . $curso->sigla . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Período (ex: 1º Período)
          <input type="text" name="periodo" required pattern="\\d+º Período" title="Use apenas '1º Período', '2º Período', etc." placeholder="Ex: 1º Período" value="<?= $modo_edicao ? htmlspecialchars($turma_editar->periodo) : '' ?>">
        </label>

        <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Turma' ?></button>
        <?php if ($modo_edicao): ?>
          <a href="gerenciar_turmas.php" class="btn-cancelar">Cancelar</a>
        <?php endif; ?>
      </form>

      <!-- Formulário para adicionar novo curso -->
      <form method="POST" class="form-novo-curso" style="margin-bottom: 2em; background: #f8f8f8; padding: 1em; border-radius: 6px;">
        <h2 style="font-size:1.1em; margin-bottom:0.5em;">Adicionar novo curso</h2>
        <label>Nome completo
          <input type="text" name="novo_curso_nome" maxlength="100" required placeholder="Ex: Engenharia de Computação">
        </label>
        <label>Sigla
          <input type="text" name="novo_curso_sigla" maxlength="10" required placeholder="Ex: EC">
        </label>
        <button type="submit">Cadastrar Curso</button>
      </form>
      <a class="btn-back" href="javascript:history.back()">Voltar</a>
    </aside>

    <div class="responsive-table">
      <!-- Filtros -->
      <form method="GET" class="filter-form">
        <select name="nome">
          <option value="">Todos os cursos</option>
          <?php foreach ($cursos as $curso): ?>
            <option value="<?= htmlspecialchars($curso->nome_completo) ?>" <?= ($filtro_nome === $curso->nome_completo) ? 'selected' : '' ?>>
              <?= htmlspecialchars($curso->nome_completo . ' (' . $curso->sigla . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="periodo">
          <option value="">Todos os períodos</option>
          <?php foreach ($periodos as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>" <?= ($filtro_periodo === $p) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>Sigla</th>
            <th>Nome do Curso</th>
            <th>Período</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($turmas as $t): ?>
            <tr>
              <td data-label="Sigla"><?= $t->sigla ?></td>
              <td data-label="Nome Curso"><?= $t->nome_completo ?></td>
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
