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

// Buscar cursos e períodos para filtros
$cursos = $conn->query("SELECT id, sigla, nome_completo FROM Curso ORDER BY nome_completo ASC")->fetchAll();
$periodos = $conn->query("SELECT DISTINCT periodo FROM Turma ORDER BY LENGTH(periodo), periodo ASC")->fetchAll();

// Parâmetros de busca e ordenação
$curso_id = isset($_GET['curso_id']) && $_GET['curso_id'] !== '' ? (int)$_GET['curso_id'] : null;
$periodo = isset($_GET['periodo']) && $_GET['periodo'] !== '' ? $_GET['periodo'] : null;
$ordenar_por = isset($_GET['ordenar_por']) && in_array($_GET['ordenar_por'], ['cota_total', 'cota_usada', 'restante']) ? $_GET['ordenar_por'] : 'cota_total';
$ordem = isset($_GET['ordem']) && in_array($_GET['ordem'], ['ASC', 'DESC']) ? $_GET['ordem'] : 'DESC';

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
            $_SESSION['mensagem'] = "Cota de $valor_cota adicionada à turma com sucesso!";
        } elseif ($acao === 'subtrair') {
            $novoTotal = $cotaAtual->cota_total - $valor_cota;
            if ($novoTotal < 0) $novoTotal = 0;
            $_SESSION['mensagem'] = "Cota de $valor_cota subtraída da turma com sucesso!";
        } else {
            $novoTotal = $cotaAtual->cota_total;
            $_SESSION['mensagem'] = "Nenhuma alteração realizada.";
        }
        $update = $conn->prepare("UPDATE CotaAluno SET cota_total = :total WHERE turma_id = :turma_id");
        $update->execute([':total' => $novoTotal, ':turma_id' => $turma_id]);
    } elseif ($acao === 'adicionar' && $valor_cota > 0) {
        $insert = $conn->prepare("INSERT INTO CotaAluno (turma_id, cota_total, cota_usada) VALUES (:turma_id, :total, 0)");
        $insert->execute([':turma_id' => $turma_id, ':total' => $valor_cota]);
        $_SESSION['mensagem'] = "Cota de $valor_cota adicionada à nova turma com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro: Nenhuma cota adicionada.";
    }
    header('Location: gerenciar_cotas.php?' . http_build_query($_GET));
    exit;
}

// Paginação
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Consulta total de cotas com filtros
$sql_count = "SELECT COUNT(*) AS total FROM CotaAluno ca JOIN Turma t ON ca.turma_id = t.id JOIN Curso c ON t.curso_id = c.id";
$paramsFiltro = [];
if ($curso_id) {
    $sql_count .= " WHERE c.id = :curso_id";
    $paramsFiltro[':curso_id'] = $curso_id;
}
if ($periodo) {
    $sql_count .= $curso_id ? " AND t.periodo = :periodo" : " WHERE t.periodo = :periodo";
    $paramsFiltro[':periodo'] = $periodo;
}
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($paramsFiltro);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

// Consulta principal com filtros e ordenação
$sql = "SELECT ca.*, c.sigla, c.nome_completo, t.periodo 
        FROM CotaAluno ca 
        JOIN Turma t ON ca.turma_id = t.id 
        JOIN Curso c ON t.curso_id = c.id";
if ($curso_id || $periodo) {
    $sql .= " WHERE";
    if ($curso_id) {
        $sql .= " c.id = :curso_id";
        $paramsFiltro[':curso_id'] = $curso_id;
    }
    if ($periodo) {
        $sql .= $curso_id ? " AND t.periodo = :periodo" : " t.periodo = :periodo";
        $paramsFiltro[':periodo'] = $periodo;
    }
}
$sql .= $ordenar_por === 'restante' ? 
        " ORDER BY (ca.cota_total - ca.cota_usada) $ordem, c.sigla ASC" : 
        " ORDER BY ca.$ordenar_por $ordem, c.sigla ASC";
$sql .= " LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($paramsFiltro as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cotas = $stmt->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/gerenciar_cotas.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
  <aside class="dashboard-aside-cotas">
    <div class="container-principal">
        <?php if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        } ?>
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
            <a class="btn-back" href="dashboard_cad.php">← Voltar</a>
        </nav>
    </div>
  </aside>
  <main class="dashboard-main">
    <!-- Exibir mensagem de sucesso -->
    <?php if (!empty($_SESSION['mensagem'])): ?>
        <div id="toast-mensagem" class="mensagem-sucesso">
            <?= htmlspecialchars($_SESSION['mensagem']) ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    <div class="container-principal">
        <!-- Formulário de Filtros -->
        <section aria-label="Filtros de busca">
            <form method="GET" class="form-filtros">
                <div class="form-group">
                    <label for="curso_id">Curso:</label>
                    <select id="curso_id" name="curso_id">
                        <option value="">Todos</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso->id ?>" <?= $curso_id == $curso->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($curso->nome_completo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="periodo">Período:</label>
                    <select id="periodo" name="periodo">
                        <option value="">Todos</option>
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= htmlspecialchars($p->periodo) ?>" <?= $periodo == $p->periodo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->periodo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="ordenar_por">Ordenar por:</label>
                    <select id="ordenar_por" name="ordenar_por">
                        <option value="cota_total" <?= $ordenar_por == 'cota_total' ? 'selected' : '' ?>>Cota Total</option>
                        <option value="cota_usada" <?= $ordenar_por == 'cota_usada' ? 'selected' : '' ?>>Cota Usada</option>
                        <option value="restante" <?= $ordenar_por == 'restante' ? 'selected' : '' ?>>Cota Restante</option>
                    </select>
                    <label for="ordem">Ordem:</label>
                    <select id="ordem" name="ordem">
                        <option value="ASC" <?= $ordem == 'ASC' ? 'selected' : '' ?>>Crescente</option>
                        <option value="DESC" <?= $ordem == 'DESC' ? 'selected' : '' ?>>Decrescente</option>
                    </select>
                    <button type="submit" class="btn-filtro">Filtrar</button>
                </div>
            </form>
        </section>
        <!-- Tabela -->
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
                    <?php if (empty($cotas)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#888;">Nenhum registro encontrado para os filtros selecionados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cotas as $cota): ?>
                            <tr>
                                <td data-label="Turma"><?= htmlspecialchars($cota->sigla) ?></td>
                                <td data-label="Período"><?= htmlspecialchars($cota->periodo) ?></td>
                                <td data-label="Cota Total"><?= $cota->cota_total ?></td>
                                <td data-label="Usada"><?= $cota->cota_usada ?></td>
                                <td data-label="Restante"><?= $cota->cota_total - $cota->cota_usada ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Paginação -->
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
    </div>
  </main>
</div>
<script>
    // Lógica para exibir e ocultar o toast
    document.addEventListener('DOMContentLoaded', () => {
        const toast = document.getElementById('toast-mensagem');
        if (toast) {
            // Exibir o toast com a classe 'show'
            toast.classList.add('show');

            // Ocultar o toast após 4 segundos
            setTimeout(() => {
                toast.classList.remove('show');

                // Aguardar o fim da transição antes de remover o elemento do DOM
                toast.addEventListener('transitionend', () => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, { once: true });
            }, 4000);
        }
    });
</script>
<?php include_once '../../includes/footer.php'; ?>