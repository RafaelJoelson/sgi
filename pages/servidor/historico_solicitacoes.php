<?php
// Histórico completo de solicitações do servidor
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}
$id_servidor = $_SESSION['usuario']['id'];
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_servidor.css">
<main class="container">
  <h1>Histórico de Solicitações</h1>
  <table style="width:100%;font-size:0.98em;">
    <thead>
      <tr>
        <th>Arquivo</th>
        <th>Cópias</th>
        <th>Páginas</th>
        <th>Tipo</th>
        <th>Status</th>
        <th>Data</th>
      </tr>
    </thead>
    <tbody>
      <?php
      try {
        $stmt = $pdo->prepare('SELECT arquivo, qtd_copias, qtd_paginas, tipo_impressao, status, DATE_FORMAT(data, "%d/%m/%Y %H:%i") as data FROM SolicitacaoImpressao WHERE id_servidor = ? ORDER BY data DESC');
        $stmt->execute([$id_servidor]);
        while ($s = $stmt->fetch(PDO::FETCH_ASSOC)) {
          echo '<tr>';
          echo '<td>' . htmlspecialchars($s['arquivo']) . '</td>';
          echo '<td>' . (int)$s['qtd_copias'] . '</td>';
          echo '<td>' . (int)$s['qtd_paginas'] . '</td>';
          echo '<td>' . ($s['tipo_impressao'] === 'colorida' ? 'Colorida' : 'PB') . '</td>';
          echo '<td>' . htmlspecialchars($s['status']) . '</td>';
          echo '<td>' . htmlspecialchars($s['data']) . '</td>';
          echo '</tr>';
        }
      } catch (Exception $e) {
        echo '<tr><td colspan="6">Erro ao carregar histórico.</td></tr>';
      }
      ?>
    </tbody>
  </table>
  <button onclick="window.location.href='dashboard_servidor.php'">Voltar ao Painel</button>
</main>
<?php require_once '../../includes/footer.php'; ?>
