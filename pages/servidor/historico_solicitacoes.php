<?php
// Histórico completo de solicitações do servidor
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}
$cpf_servidor = $_SESSION['usuario']['cpf'];
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
          $stmt = $conn->prepare('SELECT arquivo_path as arquivo, qtd_copias, qtd_paginas, colorida as tipo_impressao, status, DATE_FORMAT(data_criacao, "%d/%m/%Y %H:%i") as data FROM SolicitacaoImpressao WHERE cpf_solicitante = ? AND tipo_solicitante = "Servidor" ORDER BY data_criacao DESC');
          $stmt->execute([$cpf_servidor]);
          while ($s = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<tr>';

            // Arquivo
            if ($s['arquivo'] === '[SOLICITAÇÃO NO BALCÃO]') {
              echo '<td><em>Solicitação no balcão</em></td>';
            } else {
              $nome = htmlspecialchars($s['arquivo']);
              $link = '../../uploads/' . rawurlencode($s['arquivo']);
              echo "<td><a href=\"$link\" target=\"_blank\" download>$nome</a></td>";
            }

            echo '<td>' . (int)$s['qtd_copias'] . '</td>';
            echo '<td>' . (int)$s['qtd_paginas'] . '</td>';
            echo '<td>' . ($s['tipo_impressao'] == 1 ? 'Colorida' : 'PB') . '</td>';
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
