<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD', 'COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// Cria tabela de log se não existir
$conn->exec("CREATE TABLE IF NOT EXISTS LogSemestreLetivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    setor VARCHAR(10) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    data DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
)");

// Adicionar ou editar semestre letivo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ano = intval($_POST['ano']);
    $semestre = $_POST['semestre'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];

    // Verifica se já existe
    $stmt = $conn->prepare("SELECT id FROM SemestreLetivo WHERE ano = :ano AND semestre = :semestre");
    $stmt->execute([':ano' => $ano, ':semestre' => $semestre]);
    $existe = $stmt->fetch();

    if ($existe) {
        $update = $conn->prepare("UPDATE SemestreLetivo SET data_inicio = :inicio, data_fim = :fim WHERE id = :id");
        $update->execute([':inicio' => $data_inicio, ':fim' => $data_fim, ':id' => $existe['id']]);
        $acao = "Atualizou semestre $ano/$semestre para $data_inicio a $data_fim";
    } else {
        $insert = $conn->prepare("INSERT INTO SemestreLetivo (ano, semestre, data_inicio, data_fim) VALUES (:ano, :semestre, :inicio, :fim)");
        $insert->execute([':ano' => $ano, ':semestre' => $semestre, ':inicio' => $data_inicio, ':fim' => $data_fim]);
        $acao = "Cadastrou semestre $ano/$semestre: $data_inicio a $data_fim";
    }
    // Log da ação
    $usuario = $_SESSION['usuario']['nome'] . ' ' . $_SESSION['usuario']['sobrenome'] . ' (' . $_SESSION['usuario']['siap'] . ')';
    $setor = $_SESSION['usuario']['setor_admin'];
    $conn->prepare("INSERT INTO LogSemestreLetivo (usuario, setor, acao) VALUES (:usuario, :setor, :acao)")
        ->execute([':usuario' => $usuario, ':setor' => $setor, ':acao' => $acao]);
    $_SESSION['mensagem'] = 'Semestre salvo com sucesso!';
    // Exportação automática do log em CSV após alteração
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=log_semestre_letivo.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Usuário', 'Setor', 'Ação', 'Data']);
    $stmtExport = $conn->query("SELECT * FROM LogSemestreLetivo ORDER BY data DESC");
    while ($log = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $log['usuario'],
            $log['setor'],
            $log['acao'],
            date('d/m/Y H:i', strtotime($log['data']))
        ]);
    }
    fclose($output);
    exit;
}

// Buscar semestres cadastrados
$stmt = $conn->query("SELECT * FROM SemestreLetivo ORDER BY ano DESC, semestre DESC");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Buscar logs
$stmtLog = $conn->query("SELECT * FROM LogSemestreLetivo ORDER BY data DESC LIMIT 20");
$logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

// Exportação do log em CSV
if (isset($_GET['exportar_log']) && $_GET['exportar_log'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=log_semestre_letivo.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Usuário', 'Setor', 'Ação', 'Data']);
    $stmtExport = $conn->query("SELECT * FROM LogSemestreLetivo ORDER BY data DESC");
    while ($log = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $log['usuario'],
            $log['setor'],
            $log['acao'],
            date('d/m/Y H:i', strtotime($log['data']))
        ]);
    }
    fclose($output);
    exit;
}

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="configurar_semestre.css">
<script>
// Dupla confirmação ao salvar semestre
window.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('.form-semestre');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      if (confirm('Tem certeza que deseja salvar/alterar o semestre letivo?')) {
        if (confirm('Esta ação impacta datas e cotas institucionais. Confirma novamente?')) {
          form.submit();
        }
      }
    });
  }
  // Mensagem após exportação automática
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('msg') === '1') {
    alert('Semestre salvo com sucesso! O log de alterações foi exportado automaticamente.');
  }
});
</script>
<div class="dashboard-layout">
  <aside class="dashboard-aside">
    <h1>Configurar Semestre Letivo</h1>
    <?php if (!empty($_SESSION['mensagem'])): ?>
      <div class="mensagem-sucesso"> <?= htmlspecialchars($_SESSION['mensagem']) ?> </div>
      <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    <form method="POST" class="form-semestre" style="margin-bottom:2em;" id="form-semestre">
      <label>Ano
        <input type="number" name="ano" required min="2000" max="2100">
      </label>
      <label>Semestre
        <select name="semestre" required>
          <option value="1">1</option>
          <option value="2">2</option>
        </select>
      </label>
      <label>Data de Início
        <input type="date" name="data_inicio" required>
      </label>
      <label>Data de Fim
        <input type="date" name="data_fim" required>
      </label>
      <button type="submit">Salvar</button>
    </form>
    <nav class="btn-container" aria-label="Ações">
      <a class="btn-back" href="javascript:history.back()">Voltar</a>
    </nav>
  </aside>
  <main class="dashboard-main">
    <h2>Semestres Cadastrados</h2>
    <table>
      <thead>
        <tr>
          <th>Ano</th>
          <th>Semestre</th>
          <th>Início</th>
          <th>Fim</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($semestres as $s): ?>
          <tr>
            <td><?= $s['ano'] ?></td>
            <td><?= $s['semestre'] ?></td>
            <td><?= date('d/m/Y', strtotime($s['data_inicio'])) ?></td>
            <td><?= date('d/m/Y', strtotime($s['data_fim'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <h2 style="margin-top:2em;">Log de Alterações</h2>
    <a href="?exportar_log=csv" class="btn-cotas" style="margin-bottom:1em;display:inline-block;">Exportar Log (CSV)</a>
    <table>
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Setor</th>
          <th>Ação</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?= htmlspecialchars($log['usuario']) ?></td>
            <td><?= htmlspecialchars($log['setor']) ?></td>
            <td><?= htmlspecialchars($log['acao']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($log['data'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
</div>
<?php include_once '../../includes/footer.php'; ?>
