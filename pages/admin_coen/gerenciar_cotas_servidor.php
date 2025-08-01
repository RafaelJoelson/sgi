<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    header('Location: ../../index.php');
    exit;
}

// Lógica de transferência de cotas (sem alterações)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siape_origem = $_POST['siape_origem'];
    $siape_destino = $_POST['siape_destino'];
    $tipo_cota = $_POST['tipo_cota']; // pb ou color
    $quantidade = intval($_POST['quantidade']);

    if ($siape_origem === $siape_destino || $quantidade <= 0) {
        $_SESSION['mensagem'] = 'Erro: Verifique os dados da transferência.';
        header('Location: gerenciar_cotas_servidor.php');
        exit;
    }
    
    try {
        $conn->beginTransaction();
        $campo_total = $tipo_cota === 'color' ? 'cota_color_total' : 'cota_pb_total';
        
        // Verifica saldo
        $stmtSaldo = $conn->prepare("SELECT $campo_total FROM CotaServidor WHERE siape = :siape");
        $stmtSaldo->execute([':siape' => $siape_origem]);
        $saldo_origem = $stmtSaldo->fetchColumn();

        if ($saldo_origem < $quantidade) {
            throw new Exception('Servidor de origem não possui saldo suficiente.');
        }

        // Realiza a transferência
        $conn->prepare("UPDATE CotaServidor SET $campo_total = $campo_total - :qtd WHERE siape = :siape")
             ->execute([':qtd' => $quantidade, ':siape' => $siape_origem]);
        $conn->prepare("UPDATE CotaServidor SET $campo_total = $campo_total + :qtd WHERE siape = :siape")
             ->execute([':qtd' => $quantidade, ':siape' => $siape_destino]);
        
        $conn->commit();
        $_SESSION['mensagem'] = 'Transferência realizada com sucesso!';

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['mensagem'] = 'Erro na transferência: ' . $e->getMessage();
    }
    
    header('Location: gerenciar_cotas_servidor.php');
    exit;
}

// --- LÓGICA DE PAGINAÇÃO ---
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// MUDANÇA: Adicionado "AND s.ativo = TRUE" para contar apenas servidores ativos
$stmt_count = $conn->query("SELECT COUNT(*) AS total FROM Servidor s JOIN CotaServidor cs ON s.siape = cs.siape WHERE s.is_super_admin = FALSE AND s.ativo = TRUE");
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

// MUDANÇA: Adicionado "AND s.ativo = TRUE" para listar apenas servidores ativos na tabela
$stmt = $conn->prepare("SELECT s.siape, s.nome, s.sobrenome, cs.cota_pb_total, cs.cota_pb_usada, cs.cota_color_total, cs.cota_color_usada 
                      FROM Servidor s 
                      JOIN CotaServidor cs ON s.siape = cs.siape 
                      WHERE s.is_super_admin = FALSE AND s.ativo = TRUE
                      ORDER BY s.nome ASC, s.sobrenome ASC 
                      LIMIT :limite OFFSET :offset");
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cotas = $stmt->fetchAll();

// MUDANÇA: Adicionado "AND ativo = TRUE" para listar apenas servidores ativos nos selects
$stmtServidores = $conn->query("SELECT siape, nome, sobrenome FROM Servidor WHERE is_super_admin = FALSE AND ativo = TRUE ORDER BY nome ASC, sobrenome ASC");
$servidores = $stmtServidores->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/gerenciar_cotas_servidor.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <div class="container-principal">
        <?php if (isset($_SESSION['usuario'])) { gerar_migalhas(); } ?>
        <h1>Transferência de Cotas</h1>
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
        <a href="dashboard_coen.php" class="btn-back" style="margin-top:1.5em;">&larr; Voltar</a>
        <script>
            document.getElementById('siape_origem').addEventListener('change', function() {
                const origem = this.value;
                const destinoSelect = document.getElementById('siape_destino');
                Array.from(destinoSelect.options).forEach(opt => {
                    opt.disabled = (opt.value && opt.value === origem);
                });
                if(destinoSelect.value === origem) destinoSelect.value = '';
            });
        </script>
        </div>
    </aside>
    <main class="dashboard-main">
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>SIAPE</th>
                        <th>Nome</th>
                        <th title="Cotas Preto de Branco: Utilizadas/Total">PB: Utilizadas/Total</th>
                        <th title="Cotas Coloridas: Utilizadas/Total">Coloridas: Utilizadas/Total</th>
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
            <?php if ($total_paginas > 1): ?>
                <nav class="paginacao">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a class="<?= $i === $pagina ? 'pagina-ativa' : '' ?>"
                            href="?pagina=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include_once '../../includes/footer.php'; ?>
