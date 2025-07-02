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
if ($vigencia) {
    $sql = "SELECT s.nome, s.sobrenome, si.data_criacao, si.qtd_copias, si.qtd_paginas, (si.qtd_copias * si.qtd_paginas) as total_cotas
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
}

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_coen.css">
<main class="dashboard-layout">
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
  <main class="dashboard-main">
    <div class="responsive-table">
      <table>
        <thead>
          <tr>
            <th>Servidor</th>
            <th>Data da Solicitação</th>
            <th>Qtd. Cópias</th>
            <th>Qtd. Páginas</th>
            <th>Total de Cotas Usadas</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($relatorio as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r->nome . ' ' . $r->sobrenome) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($r->data_criacao)) ?></td>
            <td><?= $r->qtd_copias ?></td>
            <td><?= $r->qtd_paginas ?></td>
            <td><?= $r->total_cotas ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($relatorio)): ?>
            <tr><td colspan="5" style="text-align:center; color:#888;">Nenhum registro encontrado para a vigência selecionada.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</main>
<?php include_once '../../includes/footer.php'; ?>
