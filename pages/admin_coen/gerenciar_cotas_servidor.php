<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    header('Location: ../../index.php');
    exit;
}

// Buscar servidores
$stmtServidores = $conn->query("SELECT siap, nome, sobrenome FROM Servidor ORDER BY nome ASC, sobrenome ASC");
$servidores = $stmtServidores->fetchAll();

// Transferência de cotas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siap_origem = $_POST['siap_origem'];
    $siap_destino = $_POST['siap_destino'];
    $tipo_cota = $_POST['tipo_cota']; // pb ou color
    $quantidade = intval($_POST['quantidade']);

    if ($siap_origem === $siap_destino) {
        $_SESSION['mensagem'] = 'Selecione servidores diferentes para transferir.';
        header('Location: gerenciar_cotas_servidor.php');
        exit;
    }
    if ($quantidade <= 0) {
        $_SESSION['mensagem'] = 'A quantidade deve ser positiva.';
        header('Location: gerenciar_cotas_servidor.php');
        exit;
    }

    // Verifica saldo do servidor origem
    $campo_total = $tipo_cota === 'color' ? 'cota_color_total' : 'cota_pb_total';
    $stmtSaldo = $conn->prepare("SELECT $campo_total FROM CotaServidor WHERE siap = :siap");
    $stmtSaldo->execute([':siap' => $siap_origem]);
    $saldo_origem = $stmtSaldo->fetchColumn();

    if ($saldo_origem < $quantidade) {
        $_SESSION['mensagem'] = 'Servidor de origem não possui saldo suficiente.';
        header('Location: gerenciar_cotas_servidor.php');
        exit;
    }

    // Realiza a transferência
    $conn->beginTransaction();
    $conn->prepare("UPDATE CotaServidor SET $campo_total = $campo_total - :qtd WHERE siap = :siap")
        ->execute([':qtd' => $quantidade, ':siap' => $siap_origem]);
    $conn->prepare("UPDATE CotaServidor SET $campo_total = $campo_total + :qtd WHERE siap = :siap")
        ->execute([':qtd' => $quantidade, ':siap' => $siap_destino]);
    $conn->commit();

    $_SESSION['mensagem'] = 'Transferência realizada com sucesso!';
    header('Location: gerenciar_cotas_servidor.php');
    exit;
}

// Buscar cotas atuais
$stmt = $conn->query("SELECT s.siap, s.nome, s.sobrenome, cs.cota_pb_total, cs.cota_pb_usada, cs.cota_color_total, cs.cota_color_usada FROM Servidor s JOIN CotaServidor cs ON s.siap = cs.siap ORDER BY s.nome ASC, s.sobrenome ASC");
$cotas = $stmt->fetchAll();

include_once '../../includes/header.php';
?>
<main class="container">
<div class="dashboard-container">
<aside>
  <h1>Transferência de Cotas entre Servidores</h1>
  <?php if (!empty($_SESSION['mensagem'])): ?>
    <div class="mensagem-sucesso"> <?= htmlspecialchars($_SESSION['mensagem']) ?> </div>
    <?php unset($_SESSION['mensagem']); ?>
  <?php endif; ?>
  <form method="POST" class="form-cotas">
    <label>Servidor Origem
      <select name="siap_origem" required>
        <option value="" disabled selected>Selecione o servidor</option>
        <?php foreach ($servidores as $s): ?>
          <option value="<?= $s->siap ?>"><?= htmlspecialchars($s->nome . ' ' . $s->sobrenome . ' (' . $s->siap . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Servidor Destino
      <select name="siap_destino" required>
        <option value="" disabled selected>Selecione o servidor</option>
        <?php foreach ($servidores as $s): ?>
          <option value="<?= $s->siap ?>"><?= htmlspecialchars($s->nome . ' ' . $s->sobrenome . ' (' . $s->siap . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Tipo de Cota
      <select name="tipo_cota" required>
        <option value="pb">Preto e Branco</option>
        <option value="color">Colorida</option>
      </select>
    </label>
    <label>Quantidade
      <input type="number" name="quantidade" min="1" required>
    </label>
    <button type="submit">Transferir</button>
  </form>
</aside>
<div class="responsive-table">
  <table>
    <thead>
      <tr>
        <th>SIAP</th>
        <th>Nome</th>
        <th>Cota PB</th>
        <th>Cota Colorida</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($cotas as $cota): ?>
      <tr>
        <td data-label="SIAP"> <?= $cota->siap ?> </td>
        <td data-label="Nome"> <?= htmlspecialchars($cota->nome . ' ' . $cota->sobrenome) ?> </td>
        <td data-label="Cota PB"> <?= $cota->cota_pb_usada ?? 0 ?> / <?= $cota->cota_pb_total ?? 0 ?> </td>
        <td data-label="Cota Colorida"> <?= $cota->cota_color_usada ?? 0 ?> / <?= $cota->cota_color_total ?? 0 ?> </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
</main>
<?php include_once '../../includes/footer.php'; ?>
