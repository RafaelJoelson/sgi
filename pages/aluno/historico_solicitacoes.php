<?php
// Histórico de Solicitações do Aluno
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
$aluno_cpf = $_SESSION['usuario']['cpf'];
// Consulta todas as solicitações do aluno na tabela correta
$stmt = $conn->prepare('SELECT arquivo_path, qtd_copias, colorida, status, data_criacao FROM SolicitacaoImpressao WHERE cpf_solicitante = ? AND tipo_solicitante = "Aluno" ORDER BY data_criacao DESC');
$stmt->execute([$aluno_cpf]);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="dashboard_aluno.css">
<main class="container">
  <h1>Histórico de Solicitações</h1>
  <a href="dashboard_aluno.php" style="display:inline-block;margin-bottom:1em;">&larr; Voltar ao Painel</a>
  <div style="overflow-x:auto;">
    <table style="width:100%;font-size:0.98em;">
      <thead>
        <tr>
          <th>Arquivo</th>
          <th>Cópias</th>
          <th>Colorida</th>
          <th>Status</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($solicitacoes)): ?>
          <tr><td colspan="5">Nenhuma solicitação encontrada.</td></tr>
        <?php else: foreach ($solicitacoes as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['arquivo_path']) ?></td>
            <td><?= (int)$s['qtd_copias'] ?></td>
            <td><?= $s['colorida'] ? 'Sim' : 'Não' ?></td>
            <td><?= htmlspecialchars($s['status']) ?></td>
            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['data_criacao']))) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require_once '../../includes/footer.php'; ?>
