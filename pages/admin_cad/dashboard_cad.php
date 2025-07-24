<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD' || empty($_SESSION['usuario']['is_admin'])) {
    header('Location: ../../index.php');
    exit;
}

// Pega o SIAPE do admin logado para a verificação de autoexclusão na tabela
$siape_logado = $_SESSION['usuario']['id'];

// Parâmetros de paginação e busca
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 12;
$offset = ($pagina - 1) * $limite;

$condicoes = [];
$params = [];
$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');
$filtro_turma = $_GET['turma'] ?? '';

$base_sql = "FROM Aluno a 
             LEFT JOIN CotaAluno ca ON a.cota_id = ca.id
             LEFT JOIN Turma t ON ca.turma_id = t.id
             LEFT JOIN Curso c ON t.curso_id = c.id";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') $condicoes[] = "a.cpf = :valor";
    elseif ($tipo_busca === 'matricula') $condicoes[] = "a.matricula = :valor";
    $params[':valor'] = $valor_busca;
}
if (!empty($filtro_turma)) {
    $condicoes[] = "t.id = :turma_id";
    $params[':turma_id'] = $filtro_turma;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Consultas de total e principal
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

$sql_alunos = "SELECT a.*, t.periodo, c.sigla, c.nome_completo " . $base_sql . " " . $where_clause . " ORDER BY a.nome ASC LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql_alunos);
foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$alunos = $stmt->fetchAll();

// Dados para os cards e filtros
$total_turmas = $conn->query("SELECT COUNT(DISTINCT turma_id) AS total FROM CotaAluno")->fetch()->total ?? 0;
$total_alunos = $conn->query("SELECT COUNT(*) AS total FROM Aluno WHERE ativo = 1")->fetch()->total ?? 0;
$stmt_turmas = $conn->query("SELECT t.id, t.periodo, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id ORDER BY c.nome_completo ASC, t.periodo ASC");
$turmas_disponiveis = $stmt_turmas->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/dashboard_cad.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <section class="dashboard-header">
            <h1>Coordenação de Apoio ao Discente</h1>
        </section>
        <section class="dashboard-cards">
            <div class="card">Turmas Ativas: <?= $total_turmas ?></div>
            <div class="card">Alunos Ativos: <?= $total_alunos ?></div>
        </section>
        <section class="dashboard-menu">
            <a class="btn-menu" href="form_aluno.php">Cadastrar novo aluno</a>
            <a class="btn-menu" href="gerenciar_cotas.php">Gerenciar Cotas</a>
            <a class="btn-menu" href="gerenciar_turmas.php">Gerenciar Turmas</a>
            <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>
            <a class="btn-menu" href="relatorio_aluno.php">Relatório de Impressões</a>
            <a class="btn-menu" href="../servidor/dashboard_servidor.php">Acessar Modo Solicitante</a>
        </section>
    </aside>
    <main class="dashboard-main">
        <div id="toast-notification-container"></div>
        <?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
            <div id="toast-mensagem" class="mensagem-sucesso" style="display: none;">
                <?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?>
            </div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>
        <!-- Formulário de Busca -->
        <form method="GET" class="form-busca" style="margin-bottom: 1em;">
            <label>
                Tipo de Busca:
                <select name="tipo_busca" required>
                    <option value="" disabled <?= empty($tipo_busca) ? 'selected' : '' ?>>Selecione</option>
                    <option value="cpf" <?= $tipo_busca === 'cpf' ? 'selected' : '' ?>>CPF</option>
                    <option value="matricula" <?= $tipo_busca === 'matricula' ? 'selected' : '' ?>>Matrícula</option>
                </select>
            </label>
            <label>
                Valor:
                <input type="text" name="valor_busca" value="<?= htmlspecialchars($valor_busca) ?>" maxlength="11" placeholder="Digite o CPF ou Matrícula" required>
            </label>
            <label>
                Filtrar por Turma:
                <select name="turma" id="turma">
                    <option value="" <?= empty($filtro_turma) ? 'selected' : '' ?>>Todas as turmas</option>
                    <?php foreach ($turmas_disponiveis as $turma): ?>
                        <option value="<?= $turma->id ?>" <?= $filtro_turma == $turma->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($turma->nome_completo . ' - ' . $turma->periodo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Buscar</button>
            <?php if (!empty($tipo_busca) || !empty($valor_busca) || !empty($filtro_turma)): ?>
                <a href="?pagina=1" class="btn-limpar">Limpar Filtro</a>
            <?php endif; ?>
        </form>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Turma</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum aluno encontrado para os critérios de busca.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td data-label="Matrícula"><?= htmlspecialchars($aluno->matricula) ?></td>
                                <td data-label="Nome"><?= htmlspecialchars($aluno->nome . ' ' . $aluno->sobrenome) ?></td>
                                <td data-label="Cargo"><?= htmlspecialchars($aluno->cargo) ?></td>
                                <td data-label="Turma" title="<?= htmlspecialchars($aluno->nome_completo . ' - ' . $aluno->periodo) ?>"><?= htmlspecialchars($aluno->sigla . ' - ' . $aluno->periodo) ?></td>
                                <td data-label="Ações">
                                    <div class="action-buttons">
                                        <a href="form_aluno.php?matricula=<?= htmlspecialchars($aluno->matricula) ?>" class="btn-action btn-edit" title="Editar/Renovar"><i class="fas fa-edit"></i></a>
                                        <a type="button" class="btn-action btn-redefinir btn-edit" data-id="<?= htmlspecialchars($aluno->matricula) ?>" title="Redefinir Senha"><i class="fas fa-key"></i></a>
                                        <button type="button" class="btn-action btn-delete btn-excluir btn-exc" 
                                            data-id="<?= htmlspecialchars($aluno->matricula) ?>" 
                                            data-nome="<?= htmlspecialchars($aluno->nome) ?>" 
                                            data-tipo="aluno"
                                            title="Excluir Aluno">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
        <div id="modal-redefinir-aluno" class="modal">
            <div class="modal-content">
                <span class="close">×</span>
                <h2>Redefinir Senha do Aluno</h2>
                <form method="POST" action="./functions/redefinir_senha.php">
                    <input type="hidden" name="matricula" id="matricula-modal-aluno">
                    <label>Nova Senha <input type="password" name="nova_senha" required></label>
                    <button type="submit">Salvar Nova Senha</button>
                </form>
            </div>
        </div>
        <div id="modal-excluir" class="modal">
            <div class="modal-content">
                <span class="close">×</span>
                <h2>Confirmar Exclusão</h2>
                <p>Você tem certeza que deseja excluir <strong id="nome-item-excluir"></strong>?</p>
                <p>Esta ação é irreversível.</p>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary btn-cancelar-exclusao">Cancelar</button>
                    <a href="#" id="btn-confirmar-exclusao" class="btn-danger">Sim, Excluir</a>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="./js/dashboard_cad.js?v=<?= ASSET_VERSION ?>"></script>
<?php include_once '../../includes/footer.php'; ?>