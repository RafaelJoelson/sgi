<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 5; // Itens por página
$offset = ($pagina - 1) * $limite;

// Condições de busca
$condicoes = [];
$params = [];

$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');
$filtro_turma = $_GET['turma'] ?? '';

// Montagem da query base com JOIN
$base_sql = "FROM Aluno a 
             LEFT JOIN CotaAluno ca ON a.cota_id = ca.id";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') {
        $condicoes[] = "a.cpf = :valor";
    } elseif ($tipo_busca === 'matricula') {
        $condicoes[] = "a.matricula = :valor";
    }
    $params[':valor'] = $valor_busca;
} elseif (!empty($filtro_turma)) {
    $condicoes[] = "ca.turma = :turma";
    $params[':turma'] = $filtro_turma;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Total de resultados
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

// Consulta principal com LIMIT e OFFSET
$sql_alunos = "SELECT a.*, ca.turma, ca.periodo 
               " . $base_sql . " " . $where_clause . " 
               ORDER BY a.nome ASC 
               LIMIT :limite OFFSET :offset";

$stmt = $conn->prepare($sql_alunos);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$alunos = $stmt->fetchAll();

// Totais para os cards
$total_turmas = $conn->query("SELECT COUNT(DISTINCT turma, periodo) AS total FROM CotaAluno")->fetch()->total ?? 0;
$total_alunos = $conn->query("SELECT COUNT(*) AS total FROM Aluno")->fetch()->total ?? 0;

include_once '../../includes/header.php';
?>

<main class="container">
    <div class="dashboard-container">
        <aside>
          <section class="dashboard-header">
            <h1>Coordenação de Apoio ao Discente</h1>
          </section>        
            <!-- Cards -->
          <section class="dashboard-cards">
            <div class="card">Turmas Ativas: <?= $total_turmas ?></div>
            <div class="card">Alunos Ativos: <?= $total_alunos ?></div>
          </section>
          <section class="dashboard-menu">
            <a class="btn-menu" href="form_aluno.php">Cadastrar novo aluno</a>
            <a class="btn-menu" href="gerenciar_cotas.php">Gerenciar Cotas</a>
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
                    <option value="LET" <?= isset($_GET['turma']) && $_GET['turma'] === 'LET' ? 'selected' : '' ?>>Letras</option>
                    <option value="GRH" <?= isset($_GET['turma']) && $_GET['turma'] === 'GRH' ? 'selected' : '' ?>>Gestão de Recursos Humanos</option>
                    <option value="LOG" <?= isset($_GET['turma']) && $_GET['turma'] === 'LOG' ? 'selected' : '' ?>>Tecnologia em Logística</option>
                    <option value="GTI" <?= isset($_GET['turma']) && $_GET['turma'] === 'GTI' ? 'selected' : '' ?>>Gestão da Tecnologia da Informação</option>
                    <option value="GA" <?= isset($_GET['turma']) && $_GET['turma'] === 'GA' ? 'selected' : '' ?>>Gestão Ambiental</option>
                    <option value="GTEAD" <?= isset($_GET['turma']) && $_GET['turma'] === 'GTEAD' ? 'selected' : '' ?>>Gestão do Turismo EAD</option>
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
                            <td data-label="Turma"><?= htmlspecialchars($aluno->turma) ?></td>
                            <td data-label="Período"><?= htmlspecialchars($aluno->periodo) ?></td>
                            <td data-label="Validade"><?= date('d/m/Y', strtotime($aluno->data_fim_validade)) ?></td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <a href="form_aluno.php?matricula=<?= $aluno->matricula ?>">Editar/Renovar</a>
                                    <a href="excluir.php?matricula=<?= $aluno->matricula ?>">Excluir</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($total_paginas > 1): ?>
              <nav class="paginacao">
                  <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                      <a class="<?= $i === $pagina ? 'pagina-ativa' : '' ?>"
                        href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                          <?= $i ?>
                      </a>
                  <?php endfor; ?>
              </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once '../../includes/footer.php'; ?>
