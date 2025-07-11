<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
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
             LEFT JOIN CotaAluno ca ON a.cota_id = ca.id
             LEFT JOIN Turma t ON ca.turma_id = t.id
             LEFT JOIN Curso c ON t.curso_id = c.id";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') {
        $condicoes[] = "a.cpf = :valor";
    } elseif ($tipo_busca === 'matricula') {
        $condicoes[] = "a.matricula = :valor";
    }
    $params[':valor'] = $valor_busca;
}
if (!empty($filtro_turma)) {
    $condicoes[] = "t.id = :turma_id";
    $params[':turma_id'] = $filtro_turma;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Total de resultados
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

// Consulta principal com LIMIT e OFFSET
$sql_alunos = "SELECT a.*, t.periodo, c.sigla, c.nome_completo 
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
$total_turmas = $conn->query("SELECT COUNT(DISTINCT turma_id) AS total FROM CotaAluno")->fetch()->total ?? 0;
$total_alunos = $conn->query("SELECT COUNT(*) AS total FROM Aluno")->fetch()->total ?? 0;
// Buscar turmas disponíveis (JOIN com Curso para pegar sigla e nome)
$stmt_turmas = $conn->query("SELECT t.id, t.periodo, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id ORDER BY c.nome_completo ASC, t.periodo ASC");
$turmas_disponiveis = $stmt_turmas->fetchAll();


include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_cad.css">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
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
            <a class="btn-menu" href="#" id="btn-gerenciar-servidores">Gerenciar Servidores (CAD)</a>
            <a class="btn-menu" href="gerenciar_cotas.php">Gerenciar Cotas</a>
            <a class="btn-menu" href="gerenciar_turmas.php">Gerenciar Turmas</a>
            <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>
            <a class="btn-menu" href="relatorio_aluno.php">Relatório de Impressões por Aluno</a>
        </section>
    </aside>
    <main class="dashboard-main">
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
                    <?php foreach ($turmas_disponiveis as $turma): ?>
                        <option value="<?= $turma->id ?>" <?= isset($_GET['turma']) && $_GET['turma'] == $turma->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($turma->nome_completo . ' - ' . $turma->periodo) ?>
                        </option>
                    <?php endforeach; ?>
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
                            <td data-label="Turma"><?= htmlspecialchars($aluno->nome_completo) ?></td>
                            <td data-label="Período"><?= htmlspecialchars($aluno->periodo) ?></td>
                            <td data-label="Validade"><?= date('d/m/Y', strtotime($aluno->data_fim_validade)) ?></td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <a href="form_aluno.php?matricula=<?= $aluno->matricula ?>">Editar/Renovar</a>
                                    <a href="#" class="btn-redefinir" data-matricula="<?= $aluno->matricula ?>">Redefinir Senha</a>
                                    <a href="excluir.php?matricula=<?= $aluno->matricula ?>" 
                                        onclick="return confirm('Tem certeza que deseja excluir o aluno <?= $aluno->nome ?>?')">
                                        Excluir
                                    </a>
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
        <?php if (!empty($_SESSION['mensagem'])): ?>
            <div id="toast-mensagem" class="mensagem-sucesso">
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        <div id="modal-redefinir" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Redefinir Senha do Aluno</h2>
                <form method="POST" action="redefinir_senha.php">
                <input type="hidden" name="matricula" id="matricula-modal">
                <label>Nova Senha
                    <input type="password" name="nova_senha" required>
                </label>
                <button type="submit">Salvar Nova Senha</button>
                </form>
            </div>
        </div>
        <div id="modal-servidores" class="modal">
          <div class="modal-content" style="max-width:900px;width:98%;">
            <span class="close" id="close-modal-servidores">&times;</span>
            <h2>Servidores do Setor CAD</h2>
            <button onclick="window.location.href='../admin/form_servidor.php'" class="btn-menu" style="margin-bottom:1em;">Novo Servidor</button>
            <div id="tabela-servidores-cad">Carregando...</div>
          </div>
        </div>
    </main>
</div>
<script>
    document.querySelectorAll('.btn-redefinir').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const matricula = this.getAttribute('data-matricula');
        document.getElementById('matricula-modal').value = matricula;
        document.getElementById('modal-redefinir').style.display = 'block';
    });
    });

    document.querySelector('.modal .close').addEventListener('click', function () {
    document.getElementById('modal-redefinir').style.display = 'none';
    });

    window.addEventListener('click', function (e) {
    const modal = document.getElementById('modal-redefinir');
    if (e.target === modal) modal.style.display = 'none';
    });
</script>
<script>
document.getElementById('btn-gerenciar-servidores').onclick = function(e) {
  e.preventDefault();
  document.getElementById('modal-servidores').style.display = 'block';
  carregarServidoresCAD();
};
document.getElementById('close-modal-servidores').onclick = function() {
  document.getElementById('modal-servidores').style.display = 'none';
};
window.addEventListener('click', function(e) {
  const modal = document.getElementById('modal-servidores');
  if (e.target === modal) modal.style.display = 'none';
});
function carregarServidoresCAD() {
  fetch('../admin/listar_servidores_cad.php')
    .then(r => r.json())
    .then(data => {
      let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>SIAPE</th><th>Nome</th><th>Email</th><th>Setor</th><th>Ações</th></tr></thead><tbody>';
      if(data.length === 0) html += '<tr><td colspan="5">Nenhum servidor CAD encontrado.</td></tr>';
      else data.forEach(s => {
        html += `<tr>
          <td>${s.siape}</td>
          <td>${s.nome} ${s.sobrenome}</td>
          <td>${s.email}</td>
          <td>${s.setor_admin}</td>
          <td>
            <a href="../admin/form_servidor.php?siape=${encodeURIComponent(s.siape)}" class="btn-menu" style="padding:0.3rem 0.7rem;font-size:0.9em;">Editar</a>
            <a href="#" onclick="excluirServidor('${s.siape}');return false;" class="btn-menu" style="background:#c82333;">Excluir</a>
          </td>
        </tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('tabela-servidores-cad').innerHTML = html;
    });
}
function excluirServidor(siape) {
  if(!confirm('Tem certeza que deseja excluir o servidor siape ' + siape + '?')) return;
  fetch('../admin/excluir_servidor.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'siape=' + encodeURIComponent(siape)
  })
  .then(r => r.json())
  .then(data => {
    alert(data.mensagem);
    carregarServidoresCAD();
  })
  .catch(() => alert('Erro ao excluir servidor.'));
}
</script>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const toast = document.getElementById('toast-mensagem');
  if (toast) {
    // Adiciona classe para mostrar com fade in
    toast.classList.add('show');

    // Após 4 segundos, começa fade out
    setTimeout(() => {
      toast.classList.remove('show');

      // Depois do tempo da transição, remove o elemento da página
      setTimeout(() => {
        if(toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 600); // mesmo tempo da transição CSS
    }, 4000); // tempo visível da notificação
  }
});
</script>
<?php include_once '../../includes/footer.php'; ?>
