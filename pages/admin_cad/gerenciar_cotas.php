<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// Buscar turmas disponíveis (JOIN com Curso para pegar sigla e nome)
$stmtTurmas = $conn->query("SELECT t.id, t.periodo, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id ORDER BY c.nome_completo ASC, t.periodo ASC");
$turmas = $stmtTurmas->fetchAll();

// Adicionar ou editar cota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turma_id = intval($_POST['turma']);
    $valor_cota = intval($_POST['valor_cota']);
    $acao = $_POST['acao'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM CotaAluno WHERE turma_id = :turma_id");
    $stmt->execute([':turma_id' => $turma_id]);
    $cotaAtual = $stmt->fetch();

    if ($cotaAtual) {
        if ($acao === 'adicionar') {
            $novoTotal = $cotaAtual->cota_total + $valor_cota;
        } elseif ($acao === 'subtrair') {
            $novoTotal = $cotaAtual->cota_total - $valor_cota;
            if ($novoTotal < 0) $novoTotal = 0;
        } else {
            $novoTotal = $cotaAtual->cota_total;
        }
        $update = $conn->prepare("UPDATE CotaAluno SET cota_total = :total WHERE turma_id = :turma_id");
        $update->execute([':total' => $novoTotal, ':turma_id' => $turma_id]);
    } else if ($acao === 'adicionar' && $valor_cota > 0) {
        $insert = $conn->prepare("INSERT INTO CotaAluno (turma_id, cota_total, cota_usada) VALUES (:turma_id, :total, 0)");
        $insert->execute([':turma_id' => $turma_id, ':total' => $valor_cota]);
    }
    header('Location: gerenciar_cotas.php');
    exit;
}
// Buscar cotas existentes
$stmt = $conn->query("SELECT ca.*, c.sigla, c.nome_completo, t.periodo 
                      FROM CotaAluno ca 
                      JOIN Turma t ON ca.turma_id = t.id 
                      JOIN Curso c ON t.curso_id = c.id 
                      ORDER BY t.periodo DESC, c.sigla ASC");
$cotas = $stmt->fetchAll();

include_once '../../includes/header.php';
?>

<main class="container">
<div class="dashboard-container">
<aside>
  <h1>Gerenciar Cotas por Turma</h1>

  <form method="POST" class="form-cotas">
    <select name="turma" required>
      <option value="" disabled selected>Selecione a turma</option>
      <?php foreach ($turmas as $turma): ?>
        <option value="<?= $turma->id ?>"><?= htmlspecialchars($turma->nome_completo . ' - ' . $turma->periodo) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="valor_cota" min="1" placeholder="Valor da alteração" required>
    <div style="margin: 0.5em 0;">
      <button type="submit" name="acao" value="adicionar">Adicionar</button>
      <button type="submit" name="acao" value="subtrair">Subtrair</button>
    </div>
  </form>
  <div class="btn-container">
    <a class="btn-cotas" href="gerar_relatorio_pdf.php">Gerar Relatório PDF (Cotas x Alunos)</a>
    <a class="btn-cotas" href="javascript:history.back()">Voltar</a>
  </div> 
  </aside>


  <div class="responsive-table">
    <table>
      <thead>
        <tr>
          <th>Turma</th>
          <th>Período</th>
          <th>Cota Total</th>
          <th>Cota Usada</th>
          <th>Restante</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cotas as $cota): ?>
        <tr>
          <td data-label="Turma"><?= htmlspecialchars($cota->sigla) ?></td>
          <td data-label="Período"><?= htmlspecialchars($cota->periodo) ?></td>
          <td data-label="Cota Total"><?= $cota->cota_total ?></td>
          <td data-label="Usada"><?= $cota->cota_usada ?></td>
          <td data-label="Restante"><?= $cota->cota_total - $cota->cota_usada ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
  
</main>

<?php include_once '../../includes/footer.php'; ?>
