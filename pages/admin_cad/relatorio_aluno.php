<?php
// Relatório de Impressões por Aluno (CAD)
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}
// Filtros
$data_ini = $_GET['data_ini'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$pb_fisicas = isset($_GET['pb_fisicas']) ? (int)$_GET['pb_fisicas'] : 0;
$color_fisicas = isset($_GET['color_fisicas']) ? (int)$_GET['color_fisicas'] : 0;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : '';
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '';
$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : '';

// Monta consulta dinâmica
$where = "WHERE data_criacao BETWEEN :data_ini AND :data_fim AND tipo_solicitante = 'Aluno'";
$params = [':data_ini' => $data_ini . ' 00:00:00', ':data_fim' => $data_fim . ' 23:59:59'];
if ($status !== '') { $where .= " AND status = :status"; $params[':status'] = $status; }

// Consulta totalizações de impressões por aluno
$sql = "SELECT a.nome, a.sobrenome, s.colorida, SUM(s.qtd_paginas * s.qtd_copias) AS total_paginas
        FROM SolicitacaoImpressao s
        JOIN Aluno a ON a.cpf = s.cpf_solicitante
        $where
        GROUP BY a.nome, a.sobrenome, s.colorida
        ORDER BY a.nome, a.sobrenome, s.colorida";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Buscar cursos para o filtro
$cursos = $conn->query("SELECT id, sigla, nome_completo FROM Curso ORDER BY nome_completo")->fetchAll();
// Buscar períodos distintos das turmas
$periodos = $conn->query("SELECT DISTINCT periodo FROM Turma ORDER BY periodo")->fetchAll();
// Buscar turmas conforme curso e período
$turmas = [];
try {
    $turmas_sql = "SELECT t.id, t.periodo, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id WHERE 1=1";
    $params_turma = [];
    if ($curso_id) {
        $turmas_sql .= " AND c.id = :curso_id";
        $params_turma[':curso_id'] = $curso_id;
    }
    if ($periodo) {
        $turmas_sql .= " AND t.periodo = :periodo";
        $params_turma[':periodo'] = $periodo;
    }
    $turmas_sql .= " ORDER BY c.nome_completo, t.periodo";
    $stmt_turmas = $conn->prepare($turmas_sql);
    $stmt_turmas->execute($params_turma);
    $turmas = $stmt_turmas->fetchAll();
    if (!is_array($turmas)) $turmas = [];
} catch (Exception $e) {
    $turmas = [];
}

$relatorio = [];
$totais_turma = [];
$total_geral = 0;
if ($vigencia) {
    $sql = "SELECT a.nome, a.sobrenome, c.sigla, c.nome_completo, t.periodo, t.id as turma_id, si.data_criacao, si.qtd_copias, si.qtd_paginas, si.colorida, (si.qtd_copias * si.qtd_paginas) as total_cotas
            FROM SolicitacaoImpressao si
            JOIN Aluno a ON a.cpf = si.cpf_solicitante
            LEFT JOIN CotaAluno ca ON a.cota_id = ca.id
            LEFT JOIN Turma t ON ca.turma_id = t.id
            LEFT JOIN Curso c ON t.curso_id = c.id
            WHERE si.tipo_solicitante = 'Aluno'
              AND si.data_criacao BETWEEN :inicio AND :fim";
    $paramsFiltro = [
        ':inicio' => $vigencia->data_inicio . ' 00:00:00',
        ':fim' => $vigencia->data_fim . ' 23:59:59'
    ];
    if ($curso_id) {
        $sql .= " AND c.id = :curso_id";
        $paramsFiltro[':curso_id'] = $curso_id;
    }
    if ($periodo) {
        $sql .= " AND t.periodo = :periodo";
        $paramsFiltro[':periodo'] = $periodo;
    }
    if ($turma_id) {
        $sql .= " AND t.id = :turma_id";
        $paramsFiltro[':turma_id'] = $turma_id;
    }
    $sql .= " ORDER BY si.data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsFiltro);
    $relatorio = $stmt->fetchAll();
    // Soma total por turma e geral
    foreach ($relatorio as $r) {
        $tid = $r->turma_id;
        if (!isset($totais_turma[$tid])) $totais_turma[$tid] = 0;
        $totais_turma[$tid] += $r->total_cotas;
        $total_geral += $r->total_cotas;
    }
}

// Início do HTML
if (isset($_GET['imprimir']) && $_GET['imprimir'] == '1') {
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Relatório de Impressões por Aluno</title><link rel="stylesheet" href="dashboard_cad.css"></head><body>';
  echo '<div style="text-align:center;margin-bottom:2em;">
    <img src="../../img/logo-if-sjdr-nova-grafia-horizontal.png" alt="Logo IFSudesteMG" style="height:60px;margin-bottom:0.5em;"><br>
    <span style="font-size:1.3em;font-weight:bold;">Coordenação de Apoio ao Discente</span><br>
    <span style="font-size:1.1em;">Relatório de Impressões por Aluno</span><br>
    <span style="font-size:1em;">Período: ' . htmlspecialchars($data_ini) . ' a ' . htmlspecialchars($data_fim) . '</span><br>
    <span style="font-size:0.95em;color:#555;">Emitido em: ' . date('d/m/Y H:i') . '</span>
  </div>';
}
if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')) {
  require_once '../../includes/header.php';
}
?>
<?php if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')): ?>
<link rel="stylesheet" href="dashboard_cad.css">
<?php endif; ?>
<main class="dashboard-layout">
  <?php if (!(isset($_GET['imprimir']) && $_GET['imprimir'] == '1')): ?>
    <aside class="dashboard-aside">
      <h1>Relatório de Impressões por Aluno</h1>
      <form method="GET" class="relatorios-form" style="margin-bottom:1.2em;">
        <label>Vigência:
          <select name="vigencia_id" onchange="this.form.submit()">
            <?php foreach ($vigencias as $v): ?>
              <option value="<?= $v->id ?>" <?= $v->id == $vigencia_id ? 'selected' : '' ?>>
                <?= $v->ano . '/' . $v->semestre ?> (<?= date('d/m/Y', strtotime($v->data_inicio)) ?> a <?= date('d/m/Y', strtotime($v->data_fim)) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Curso:
          <select name="curso_id" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($cursos as $c): ?>
              <option value="<?= $c->id ?>" <?= $curso_id == $c->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($c->nome_completo) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Período:
          <select name="periodo" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($periodos as $p): ?>
              <option value="<?= htmlspecialchars($p->periodo) ?>" <?= $periodo == $p->periodo ? 'selected' : '' ?>>
                <?= htmlspecialchars($p->periodo) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </form>
      <button type="button" class="relatorios-imprimir" style="margin-bottom:1em;" onclick="window.open('relatorio_aluno.php?vigencia_id=<?= $vigencia_id ?>&curso_id=<?= $curso_id ?>&periodo=<?= urlencode($periodo) ?>&turma_id=<?= $turma_id ?>&imprimir=1','_blank')">Imprimir</button>
      <a class="btn-back" href="dashboard_cad.php">Voltar ao Painel</a>
    </aside>
  <?php endif; ?>
  <section class="dashboard-main">
    <div class="responsive-table">
      <table>
        <thead>
          <tr>
            <th>Sigla</th>
            <th>Curso</th>
            <th>Turma</th>
            <th>Aluno</th>
            <th>Data da Solicitação</th>
            <th>Qtd. Cotas Usadas</th>
            <th>Total da Turma</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($relatorio as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r->sigla) ?></td>
            <td><?= htmlspecialchars($r->nome_completo) ?></td>
            <td><?= htmlspecialchars($r->periodo) ?></td>
            <td><?= htmlspecialchars($r->nome . ' ' . $r->sobrenome) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($r->data_criacao)) ?></td>
            <td><?= $r->total_cotas ?></td>
            <td><?= isset($totais_turma[$r->turma_id]) ? $totais_turma[$r->turma_id] : '-' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($relatorio)): ?>
            <tr><td colspan="7" style="text-align:center; color:#888;">Nenhum registro encontrado para a vigência/turma selecionada.</td></tr>
          <?php else: ?>
            <tr style="font-weight:bold;background:#f3f3f3;">
              <td colspan="6" style="text-align:right;">Total geral (todas as turmas listadas):</td>
              <td><?= $total_geral ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php
if (isset($_GET['imprimir']) && $_GET['imprimir'] == '1') {
  echo '<link rel="stylesheet" href="../../print_base.css">
';
  echo '<script>window.onload=function(){window.print();}</script>';
  echo '</body></html>';
} else {
  require_once '../../includes/footer.php';
}