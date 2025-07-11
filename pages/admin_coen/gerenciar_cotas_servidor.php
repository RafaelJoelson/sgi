<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    header('Location: ../../index.php');
    exit;
}

// Buscar servidores
$stmtServidores = $conn->query("SELECT siape, nome, sobrenome FROM Servidor ORDER BY nome ASC, sobrenome ASC");
$servidores = $stmtServidores->fetchAll();

// Transferência de cotas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siape_origem = $_POST['siape_origem'];
    $siape_destino = $_POST['siape_destino'];
    $tipo_cota = $_POST['tipo_cota']; // pb ou color
    $quantidade = intval($_POST['quantidade']);

    if ($siape_origem === $siape_destino) {
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
    $stmtSaldo = $conn->prepare("SELECT $campo_total FROM CotaServidor WHERE siape = :siape");
    $stmtSaldo->execute([':siape' => $siape_origem]);
    $saldo_origem = $stmtSaldo->fetchColumn();

    if ($saldo_origem < $quantidade) {
        $_SESSION['mensagem'] = 'Servidor de origem não possui saldo suficiente.';
        header('Location: gerenciar_cotas_servidor.php');
        exit;
    }

    // Realiza a transferência
    $conn->beginTransaction();
    $conn->prepare("UPDATE CotaServidor SET $campo_total = $campo_total - :qtd WHERE siape = :siape")
        ->execute([':qtd' => $quantidade, ':siape' => $siape_origem]);
    $conn->prepare("UPDATE CotaServidor SET $campo_total = $campo_total + :qtd WHERE siape = :siape")
        ->execute([':qtd' => $quantidade, ':siape' => $siape_destino]);
    $conn->commit();

    $_SESSION['mensagem'] = 'Transferência realizada com sucesso!';
    header('Location: gerenciar_cotas_servidor.php');
    exit;
}

// Buscar cotas atuais
$stmt = $conn->query("SELECT s.siape, s.nome, s.sobrenome, cs.cota_pb_total, cs.cota_pb_usada, cs.cota_color_total, cs.cota_color_usada FROM Servidor s JOIN CotaServidor cs ON s.siape = cs.siape ORDER BY s.nome ASC, s.sobrenome ASC");
$cotas = $stmt->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="gerenciar_cotas_servidor.css">
<div class="dashboard-layout">
  <aside class="dashboard-aside">
    <h1>Transferência de Cotas entre Servidores</h1>
    <?php if (!empty($_SESSION['mensagem'])): ?>
      <div class="mensagem-sucesso"> <?= htmlspecialchars($_SESSION['mensagem']) ?> </div>
      <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    <form method="POST" class="form-cotas" id="form-cotas">
      <label>Servidor Origem
        <select name="siape_origem" id="siape_origem" required>
          <option value="" disabled selected>Selecione o servidor</option>
          <?php foreach ($servidores as $s): ?>
            <option value="<?= $s->siape ?>"><?= htmlspecialchars($s->nome . ' ' . $s->sobrenome . ' (' . $s->siape . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Servidor Destino
        <select name="siape_destino" id="siape_destino" required>
          <option value="" disabled selected>Selecione o servidor</option>
          <?php foreach ($servidores as $s): ?>
            <option value="<?= $s->siape ?>"><?= htmlspecialchars($s->nome . ' ' . $s->sobrenome . ' (' . $s->siape . ')') ?></option>
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
    <a href="dashboard_coen.php" class="btn-back" style="margin-top:1.5em;">Voltar</a>
    <script>
      // Remove o servidor origem da lista de destino
      document.getElementById('siape_origem').addEventListener('change', function() {
        const origem = this.value;
        const destinoSelect = document.getElementById('siape_destino');
        Array.from(destinoSelect.options).forEach(opt => {
          opt.disabled = (opt.value && opt.value === origem);
        });
        // Se o destino selecionado for igual ao origem, limpa
        if(destinoSelect.value === origem) destinoSelect.value = '';
      });
    </script>
  </aside>
  <main class="dashboard-main">
    <div class="responsive-table">
      <table>
        <thead>
          <tr>
            <th>siape</th>
            <th>Nome</th>
            <th>Cota PB</th>
            <th>Cota Colorida</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cotas as $cota): ?>
          <tr>
            <td data-label="siape"> <?= $cota->siape ?> </td>
            <td data-label="Nome"> <?= htmlspecialchars($cota->nome . ' ' . $cota->sobrenome) ?> </td>
            <td data-label="Cota PB"> <?= $cota->cota_pb_usada ?? 0 ?> / <?= $cota->cota_pb_total ?? 0 ?> </td>
            <td data-label="Cota Colorida"> <?= $cota->cota_color_usada ?? 0 ?> / <?= $cota->cota_color_total ?? 0 ?> </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php include_once '../../includes/footer.php'; ?>