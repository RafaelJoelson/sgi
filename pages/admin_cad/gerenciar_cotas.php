<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Adicionar ou editar cota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turma = trim($_POST['turma']);
    $periodo = trim($_POST['periodo']);
    $cota_total = intval($_POST['cota_total']);

    $stmt = $conn->prepare("SELECT * FROM CotaAluno WHERE turma = :turma AND periodo = :periodo");
    $stmt->execute([':turma' => $turma, ':periodo' => $periodo]);

    if ($stmt->rowCount() > 0) {
        // Atualiza
        $update = $conn->prepare("UPDATE CotaAluno SET cota_total = :total WHERE turma = :turma AND periodo = :periodo");
        $update->execute([':total' => $cota_total, ':turma' => $turma, ':periodo' => $periodo]);
    } else {
        // Insere
        $insert = $conn->prepare("INSERT INTO CotaAluno (turma, periodo, cota_total, cota_usada) VALUES (:turma, :periodo, :total, 0)");
        $insert->execute([':turma' => $turma, ':periodo' => $periodo, ':total' => $cota_total]);
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
    <input type="number" name="cota_total" placeholder="Cota total" min="0" required>
    <button type="submit">Salvar</button>
  </form>

  <a href="gerar_relatorio_pdf.php" target="_blank" style="display:inline-block;margin-bottom:1rem;">📄 Gerar Relatório PDF (Cotas x Alunos)</a>
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
