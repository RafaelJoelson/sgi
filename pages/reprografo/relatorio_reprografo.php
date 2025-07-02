<?php
// Relatório do Reprográfo
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    header('Location: ../../index.php');
    exit;
}
// Filtros
$data_ini = $_GET['data_ini'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$pb_fisicas = isset($_GET['pb_fisicas']) ? (int)$_GET['pb_fisicas'] : 0;
$color_fisicas = isset($_GET['color_fisicas']) ? (int)$_GET['color_fisicas'] : 0;

// Monta consulta dinâmica
$where = "WHERE data_criacao BETWEEN :data_ini AND :data_fim";
$params = [':data_ini' => $data_ini . ' 00:00:00', ':data_fim' => $data_fim . ' 23:59:59'];
if ($status !== '') { $where .= " AND status = :status"; $params[':status'] = $status; }
if ($tipo !== '') { $where .= " AND tipo_solicitante = :tipo"; $params[':tipo'] = $tipo; }

// Consulta totalizações de impressões por tipo de usuário e cor
$sql = "SELECT tipo_solicitante, colorida, SUM(qtd_paginas * qtd_copias) AS total_paginas FROM SolicitacaoImpressao $where GROUP BY tipo_solicitante, colorida ORDER BY tipo_solicitante, colorida";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Início do HTML
if (isset($_GET['imprimir']) && $_GET['imprimir'] == '1') {
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Relatório de Impressões</title><link rel="stylesheet" href="dashboard_reprografo.css"></head><body>';
  echo '<div style="text-align:center;margin-bottom:2em;">
    <img src="../../img/logo_horizontal_ifsudestemg.png" alt="Logo IFSudesteMG" style="height:60px;margin-bottom:0.5em;"><br>
    <span style="font-size:1.3em;font-weight:bold;">Instituto Federal do Sudeste de Minas Gerais - Campus São João Del-Rei</span><br>
    <span style="font-size:1.1em;">Relatório de Impressões do Campus</span><br>
    <span style="font-size:1em;">Período: ' . htmlspecialchars($data_ini) . ' a ' . htmlspecialchars($data_fim) . '</span><br>
    <span style="font-size:0.95em;color:#555;">Emitido em: ' . date('d/m/Y H:i') . '</span>
  </div>';
}
if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')) {
  require_once '../../includes/header.php';
}
?>
<?php if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')): ?>
<link rel="stylesheet" href="dashboard_relatorio_reprografo.css">
<?php endif; ?>
<main class="dashboard-layout">
  <?php if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')): ?>
    <aside class="dashboard-aside">
      <h1>Relatório de Impressões</h1>
      <form method="get" class="relatorios-form">
        <label>Data inicial <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>"></label>
        <label>Data final <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>"></label>
        <label>Tipo
          <select name="tipo">
            <option value="">Todos</option>
            <option value="Aluno" <?= $tipo=='Aluno'?'selected':'' ?>>Aluno</option>
            <option value="Servidor" <?= $tipo=='Servidor'?'selected':'' ?>>Servidor</option>
          </select>
        </label>
        <label>PB físicas <input type="number" name="pb_fisicas" min="0" value="<?= $pb_fisicas ?>"></label>
        <label>Coloridas físicas <input type="number" name="color_fisicas" min="0" value="<?= $color_fisicas ?>"></label>
        <button type="submit">Filtrar</button>
      </form>
      <button type="button" class="relatorios-imprimir" onclick="window.open('relatorio_reprografo.php?'+new URLSearchParams(new FormData(document.querySelector('.relatorios-form')))+'&imprimir=1','_blank')">Imprimir</button>
      <nav class="btn-container" aria-label="Ações">
        <a class="btn-back" href="dashboard_reprografo.php">Voltar ao Painel</a>
      </nav>
    </aside>
  <?php endif; ?>
  <main class="dashboard-main">
    <table class="relatorios-table">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Cor</th>
          <th>Total de Páginas Impressas</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $total_pb = 0;
        $total_color = 0;
        if (empty($dados)):
        ?>
          <tr><td colspan="3">Nenhum dado encontrado.</td></tr>
        <?php else:
          foreach ($dados as $d):
            if (!$d['colorida']) {
              $total_pb += (int)$d['total_paginas'];
            } else if ($d['tipo_solicitante'] === 'Servidor') {
              $total_color += (int)$d['total_paginas'];
            }
        ?>
          <tr>
            <td><?= $d['tipo_solicitante'] === 'Aluno' ? 'Alunos' : ($d['tipo_solicitante'] === 'Servidor' ? 'Servidores' : htmlspecialchars($d['tipo_solicitante'])) ?></td>
            <td><?= $d['colorida'] ? 'Colorida' : 'PB' ?></td>
            <td><?= (int)$d['total_paginas'] ?></td>
          </tr>
        <?php endforeach; ?>
          <tr style="background:#f9f9f9;">
            <td colspan="2">PB físicas (lançamento manual)</td>
            <td><?= $pb_fisicas ?></td>
          </tr>
          <tr style="background:#f9f9f9;">
            <td colspan="2">Coloridas físicas (lançamento manual)</td>
            <td><?= $color_fisicas ?></td>
          </tr>
          <tr style="font-weight:bold;background:#f3f3f3;">
            <td colspan="2">Total PB (Alunos + Servidores)</td>
            <td><?= $total_pb + $pb_fisicas ?></td>
          </tr>
          <tr style="font-weight:bold;background:#e3e3ff;">
            <td colspan="2">Total Coloridas (Servidores)</td>
            <td><?= $total_color + $color_fisicas ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')): ?>
    <?php endif; ?>
  </main>
</main>
<?php
if (isset($_GET['imprimir']) && $_GET['imprimir'] == '1') {
  echo '<style>body,main{background:#fff!important;} form,.container > a,button {display:none!important;} table{margin-top:2em;} h1{display:none;} @media print{button{display:none!important;}} .print-header{display:block!important;}</style>';
  echo '<script>window.onload=function(){window.print();}</script>';
  echo '</body></html>';
} else {
  require_once '../../includes/footer.php';
}
