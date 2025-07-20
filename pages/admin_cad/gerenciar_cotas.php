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

// Parâmetros de paginação e busca


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

// Paginação
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Consulta total de cotas
$sql_count = "SELECT COUNT(*) AS total FROM CotaAluno";
$total_resultados = $conn->query($sql_count)->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

// Consulta principal com paginação
$stmt = $conn->prepare("SELECT ca.*, c.sigla, c.nome_completo, t.periodo 
                      FROM CotaAluno ca 
                      JOIN Turma t ON ca.turma_id = t.id 
                      JOIN Curso c ON t.curso_id = c.id 
                      ORDER BY t.periodo DESC, c.sigla ASC 
                      LIMIT :limite OFFSET :offset");
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cotas = $stmt->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="gerenciar_cotas.css">
<div class="dashboard-layout">
  <aside class="dashboard-aside-cotas">
    <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
    <h1>Gerenciar Cotas por Turma</h1>
    <section aria-label="Formulário de cotas">
      <form method="POST" class="form-cotas">
        <fieldset style="border:0;padding:0;margin:0;">
          <legend style="font-size:1.1em;font-weight:600;margin-bottom:0.5em;">Alterar cota de turma</legend>
          <label for="turma">Turma</label>
          <select id="turma" name="turma" required>
            <option value="" disabled selected>Selecione a turma</option>
            <?php foreach ($turmas as $turma): ?>
              <option value="<?= $turma->id ?>"><?= htmlspecialchars($turma->nome_completo . ' - ' . $turma->periodo) ?></option>
            <?php endforeach; ?>
          </select>
          <label for="valor_cota">Valor da alteração</label>
          <input type="number" id="valor_cota" name="valor_cota" min="1" placeholder="Valor da alteração" required>
          <div style="margin: 0.5em 0; display: flex; gap: 0.5em; flex-wrap: wrap;">
            <button type="submit" name="acao" value="adicionar">Adicionar</button>
            <button type="submit" name="acao" value="subtrair">Subtrair</button>
          </div>
        </fieldset>
      </form>
    </section>
    <nav class="btn-container" aria-label="Ações">
      <a class="btn-back" href="dashboard_cad.php">&larr; Voltar</a>
    </nav>
  </aside>
  <main class="dashboard-main">
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
      <?php if ($total_paginas > 1): ?>
                <nav class="paginacao">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a class="<?= $i === $pagina ? 'pagina-ativa' : '' ?>"
                            href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
