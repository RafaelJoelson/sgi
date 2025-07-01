<?php
// Relatório do Reprográfo
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
// Filtros
$data_ini = $_GET['data_ini'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$colorida = $_GET['colorida'] ?? '';

// Monta consulta dinâmica
$where = "WHERE data_criacao BETWEEN :data_ini AND :data_fim";
$params = [':data_ini' => $data_ini . ' 00:00:00', ':data_fim' => $data_fim . ' 23:59:59'];
if ($status !== '') { $where .= " AND status = :status"; $params[':status'] = $status; }
if ($tipo !== '') { $where .= " AND tipo_solicitante = :tipo"; $params[':tipo'] = $tipo; }
if ($colorida !== '') { $where .= " AND colorida = :colorida"; $params[':colorida'] = $colorida; }

$sql = "SELECT tipo_solicitante, colorida, status, COUNT(*) AS total_solicitacoes, SUM(qtd_paginas * qtd_copias) AS total_paginas FROM SolicitacaoImpressao $where GROUP BY tipo_solicitante, colorida, status ORDER BY tipo_solicitante, colorida, status";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel='stylesheet' href='dashboard_reprografo.css'>
<main class="container">
  <h1>Relatório de Impressões</h1>
  <form method="get" style="margin-bottom:1em;">
    <label>Data inicial <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>"></label>
    <label>Data final <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>"></label>
    <label>Status
      <select name="status">
        <option value="">Todos</option>
        <option value="Nova" <?= $status=='Nova'?'selected':'' ?>>Nova</option>
        <option value="Lida" <?= $status=='Lida'?'selected':'' ?>>Lida</option>
        <option value="Aceita" <?= $status=='Aceita'?'selected':'' ?>>Aceita</option>
        <option value="Rejeitada" <?= $status=='Rejeitada'?'selected':'' ?>>Rejeitada</option>
      </select>
    </label>
    <label>Tipo
      <select name="tipo">
        <option value="">Todos</option>
        <option value="Aluno" <?= $tipo=='Aluno'?'selected':'' ?>>Aluno</option>
        <option value="Servidor" <?= $tipo=='Servidor'?'selected':'' ?>>Servidor</option>
      </select>
    </label>
    <label>Colorida
      <select name="colorida">
        <option value="">Todas</option>
        <option value="0" <?= $colorida==='0'?'selected':'' ?>>Não</option>
        <option value="1" <?= $colorida==='1'?'selected':'' ?>>Sim</option>
      </select>
    </label>
    <button type="submit">Filtrar</button>
  </form>
  <table style="width:100%;font-size:0.98em;">
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Colorida</th>
        <th>Status</th>
        <th>Total Solicitações</th>
        <th>Total Páginas</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($dados)): ?>
        <tr><td colspan="5">Nenhum dado encontrado.</td></tr>
      <?php else: foreach ($dados as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['tipo_solicitante']) ?></td>
          <td><?= $d['colorida'] ? 'Sim' : 'Não' ?></td>
          <td><?= htmlspecialchars($d['status']) ?></td>
          <td><?= (int)$d['total_solicitacoes'] ?></td>
          <td><?= (int)$d['total_paginas'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  <a href="dashboard_reprografo.php" style="display:inline-block;margin-top:1em;">&larr; Voltar ao Painel</a>
</main>
<?php require_once '../../includes/footer.php'; ?>
