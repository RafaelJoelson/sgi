<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Adicionar ou editar cota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turma = strtoupper(trim($_POST['turma']));
    $periodo = trim($_POST['periodo']);
    $valor_cota = intval($_POST['cota_total']); // pode ser positivo ou negativo

    $stmt = $conn->prepare("SELECT * FROM CotaAluno WHERE turma = :turma AND periodo = :periodo");
    $stmt->execute([':turma' => $turma, ':periodo' => $periodo]);
    $cotaAtual = $stmt->fetch();

    if ($cotaAtual) {
        $novoTotal = $cotaAtual->cota_total + $valor_cota;
        if ($novoTotal < 0) $novoTotal = 0; // evita total negativo

        $update = $conn->prepare("UPDATE CotaAluno SET cota_total = :total WHERE turma = :turma AND periodo = :periodo");
        $update->execute([':total' => $novoTotal, ':turma' => $turma, ':periodo' => $periodo]);
    } else {
        // Se não existir, só insere se o valor for positivo
        if ($valor_cota > 0) {
            $insert = $conn->prepare("INSERT INTO CotaAluno (turma, periodo, cota_total, cota_usada) VALUES (:turma, :periodo, :total, 0)");
            $insert->execute([':turma' => $turma, ':periodo' => $periodo, ':total' => $valor_cota]);
        }
        // Se valor for negativo e não existe cota, pode ignorar ou dar erro
    }

    header('Location: gerenciar_cotas.php');
    exit;
}
// Buscar cotas existentes
$stmt = $conn->query("SELECT * FROM CotaAluno ORDER BY periodo DESC, turma ASC");
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
        <option value="LET">Letras (Habilitação Português/Espanhol)</option>
        <option value="GRH">Tecnologia em Gestão de Recursos Humanos</option>
        <option value="LOG">Tecnologia em Logística</option>
        <option value="GTI">Tecnologia em Gestão da Tecnologia da Informação</option>
        <option value="GA">Tecnologia em Gestão Ambiental</option>
        <option value="GTEAD">Tecnologia em Gestão do Turismo EAD</option>
    </select>
    <input type="text" name="periodo" placeholder="Período (ex: 2025.2)" pattern="^[0-9]{4}\.[12]$" title="Formato: 2025.1 ou 2025.2" required>
    <input type="number" name="cota_total" placeholder="Valor para adicionar (+) ou subtrair (-)" required>
    <small>Use número negativo para diminuir a cota total.</small>
    <button type="submit">Salvar</button>
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
          <td data-label="Turma"><?= $cota->turma ?></td>
          <td data-label="Período"><?= $cota->periodo ?></td>
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
