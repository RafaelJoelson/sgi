<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    header('Location: ../../index.php');
    exit;
}

// Buscar semestre letivo vigente ou selecionado
$vigencia_id = isset($_GET['vigencia_id']) ? (int)$_GET['vigencia_id'] : null;
$vigencias = $conn->query("SELECT * FROM SemestreLetivo ORDER BY data_fim DESC")->fetchAll();
if ($vigencia_id) {
    $vigencia = $conn->prepare("SELECT * FROM SemestreLetivo WHERE id = :id");
    $vigencia->execute([':id' => $vigencia_id]);
    $vigencia = $vigencia->fetch();
} else {
    $hoje = date('Y-m-d');
    $vigencia = $conn->prepare("SELECT * FROM SemestreLetivo WHERE data_inicio <= :hoje AND data_fim >= :hoje ORDER BY data_fim DESC LIMIT 1");
    $vigencia->execute([':hoje' => $hoje]);
    $vigencia = $vigencia->fetch();
    $vigencia_id = $vigencia ? $vigencia->id : null;
}

$relatorio = [];
$totais_servidor_pb = [];
$totais_servidor_color = [];
$total_geral_pb = 0;
$total_geral_color = 0;
if ($vigencia) {
    $sql = "SELECT s.nome, s.sobrenome, si.data_criacao, si.colorida,
                   (si.qtd_copias * si.qtd_paginas) as total_cotas
            FROM SolicitacaoImpressao si
            JOIN Servidor s ON si.cpf_solicitante = s.cpf
            WHERE si.tipo_solicitante = 'Servidor'
              AND si.data_criacao BETWEEN :inicio AND :fim
            ORDER BY si.data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':inicio' => $vigencia->data_inicio . ' 00:00:00',
        ':fim' => $vigencia->data_fim . ' 23:59:59'
    ]);
    $relatorio = $stmt->fetchAll();
    // Soma total por servidor e geral (PB e colorida)
    foreach ($relatorio as $r) {
        $nome = $r->nome . ' ' . $r->sobrenome;
        if (!isset($totais_servidor_pb[$nome])) $totais_servidor_pb[$nome] = 0;
        if (!isset($totais_servidor_color[$nome])) $totais_servidor_color[$nome] = 0;
        if ($r->colorida) {
            $totais_servidor_color[$nome] += $r->total_cotas;
            $total_geral_color += $r->total_cotas;
        } else {
            $totais_servidor_pb[$nome] += $r->total_cotas;
            $total_geral_pb += $r->total_cotas;
        }
    }
}

$modo_impressao = isset($_GET['imprimir']) && $_GET['imprimir'] == '1';
if (!$modo_impressao) include_once '../../includes/header.php';
?>
<?php if (!$modo_impressao): ?>
<link rel="stylesheet" href="dashboard_coen.css">
<?php else: ?>
<style>
body { background: #fff; color: #222; font-family: Arial, sans-serif; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { border: 1px solid #bbb; padding: 6px 8px; }
th { background: #eee; }
tfoot td { font-weight: bold; background: #f3f3f3; }
@media print { button, .btn-back, form, aside { display: none !important; } }
</style>
<script>window.onload = function() { window.print(); }</script>
<?php endif; ?>
<main class="dashboard-layout">
<?php if (!$modo_impressao): ?>
  <aside class="dashboard-aside">
    <h1>Relatório de Impressões por Servidor</h1>
    <form method="GET" style="margin-bottom:1.2em;">
      <label>Vigência:
        <select name="vigencia_id" onchange="this.form.submit()">
          <?php foreach ($vigencias as $v): ?>
            <option value="<?= $v->id ?>" <?= $v->id == $vigencia_id ? 'selected' : '' ?>>
              <?= $v->ano . '/' . $v->semestre ?> (<?= date('d/m/Y', strtotime($v->data_inicio)) ?> a <?= date('d/m/Y', strtotime($v->data_fim)) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </form>
    <button type="button" class="relatorios-imprimir" style="margin-bottom:1em;" onclick="window.open('relatorio_servidor.php?vigencia_id=<?= $vigencia_id ?>&imprimir=1','_blank')">Imprimir</button>
    <a href="dashboard_coen.php" class="btn-back">Voltar para Dashboard</a>
  </aside>
<?php endif; ?>
  <main class="dashboard-main">
    <?php if ($modo_impressao): ?>
    <div style="text-align:center; margin-bottom:18px;">
      <img src="../../img/logo-if.png" alt="Logo IF" style="height:48px;vertical-align:middle;margin-right:12px;">
      <span style="font-size:1.5em;font-weight:bold;vertical-align:middle;">Relatório de Impressões por Servidor</span><br>
      <span style="font-size:1.1em; color:#444;">Vigência: <?= $vigencia ? ($vigencia->ano . '/' . $vigencia->semestre . ' (' . date('d/m/Y', strtotime($vigencia->data_inicio)) . ' a ' . date('d/m/Y', strtotime($vigencia->data_fim)) . ')') : '-' ?></span>
    </div>
    <?php endif; ?>
    <div class="responsive-table">
      <table>
        <thead>
          <tr>
            <th>Servidor</th>
            <th>Data da Solicitação</th>
            <th>Qtd. PB</th>
            <th>Qtd. Colorida</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($relatorio as $r):
            $nome = $r->nome . ' ' . $r->sobrenome;
            $qtd_pb = !$r->colorida ? $r->total_cotas : 0;
            $qtd_color = $r->colorida ? $r->total_cotas : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($nome) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($r->data_criacao)) ?></td>
            <td><?= $qtd_pb ?></td>
            <td><?= $qtd_color ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($relatorio)): ?>
            <tr><td colspan="4" style="text-align:center; color:#888;">Nenhum registro encontrado para a vigência selecionada.</td></tr>
          <?php else: ?>
            <tr style="font-weight:bold;background:#f3f3f3;">
              <td colspan="2" style="text-align:right;">Total geral (todos os servidores):</td>
              <td><?= $total_geral_pb ?></td>
              <td><?= $total_geral_color ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <!-- Tabela de totais semestrais por servidor -->
    <div class="responsive-table" style="margin-top:2em;">
      <h2 style="font-size:1.1em;margin-bottom:0.5em;">Total Semestral por Servidor</h2>
      <table>
        <thead>
          <tr>
            <th>Servidor</th>
            <th>Total PB no Semestre</th>
            <th>Total Colorida no Semestre</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Gera lista única de servidores presentes no relatório
          $servidores = array_unique(array_map(function($r) { return $r->nome . ' ' . $r->sobrenome; }, $relatorio));
          foreach ($servidores as $nome):
            $total_pb = isset($totais_servidor_pb[$nome]) ? $totais_servidor_pb[$nome] : 0;
            $total_color = isset($totais_servidor_color[$nome]) ? $totais_servidor_color[$nome] : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($nome) ?></td>
            <td><?= $total_pb ?></td>
            <td><?= $total_color ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($servidores)): ?>
            <tr><td colspan="3" style="text-align:center; color:#888;">Nenhum registro encontrado para a vigência selecionada.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</main>
<?php if (!$modo_impressao) include_once '../../includes/footer.php'; ?>
