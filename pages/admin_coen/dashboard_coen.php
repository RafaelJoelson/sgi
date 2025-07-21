<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas administradores podem acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    header('Location: ../../index.php');
    exit;
}

// Pega o SIAPE do admin logado para a verificação de autoexclusão na tabela
$siape_logado = $_SESSION['usuario']['id'];

// Parâmetros de paginação e busca
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 12;
$offset = ($pagina - 1) * $limite;

$condicoes = [];
$params = [];
$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');

$base_sql = "FROM Servidor s LEFT JOIN CotaServidor cs ON s.siape = cs.siape";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') $condicoes[] = "s.cpf = :valor";
    elseif ($tipo_busca === 'siape') $condicoes[] = "s.siape = :valor";
    $params[':valor'] = $valor_busca;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Consultas de total e principal
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

$sql_servidores = "SELECT s.*, cs.cota_pb_total, cs.cota_pb_usada, cs.cota_color_total, cs.cota_color_usada 
                   " . $base_sql . " " . $where_clause . " 
                   ORDER BY s.nome ASC 
                   LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql_servidores);
foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$servidores = $stmt->fetchAll();

$total_servidores = $conn->query("SELECT COUNT(*) AS total FROM Servidor WHERE ativo = 1")->fetch()->total ?? 0;

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_coen.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <section class="dashboard-header">
            <h1>Coordenação de Ensino</h1>
            <p>(Gerenciamento de Servidores)</p>
        </section>
        <section class="dashboard-cards">
            <div class="card">Servidores Ativos: <?= $total_servidores ?></div>
        </section>
        <section class="dashboard-menu">
            <a class="btn-menu" href="../admin/form_servidor.php">Cadastrar Novo Servidor</a>
            <a class="btn-menu" href="gerenciar_cotas_servidor.php">Gerenciar Cotas de Servidor</a>
            <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>
            <a class="btn-menu" href="relatorio_servidor.php">Relatório de Impressões</a>
            <a class="btn-menu" href="../servidor/dashboard_servidor.php">Acessar Modo Solicitante</a>
            <a class="btn-menu" href="../admin/simular_cron.php">Simular Cron</a>
        </section>
    </aside>
    <main class="dashboard-main">
        <?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
            <div id="toast-mensagem" class="mensagem-sucesso">
                <?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?>
            </div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>

        <div class="responsive-table">
            <form method="GET" class="busca-form styled-busca-form">
                <label for="tipo_busca">Buscar por:</label>
                <select name="tipo_busca" id="tipo_busca" required>
                    <option value="siape" <?= ($tipo_busca === 'siape' ? 'selected' : '') ?>>SIAPE</option>
                    <option value="cpf" <?= ($tipo_busca === 'cpf' ? 'selected' : '') ?>>CPF</option>
                </select>
                <input type="text" name="valor_busca" placeholder="Digite o SIAPE ou CPF" value="<?= htmlspecialchars($valor_busca) ?>">
                <button type="submit">Buscar</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>SIAPE</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Setor Admin</th>
                        <th>Cota PB</th>
                        <th>Cota Colorida</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servidores as $s): ?>
                        <tr>
                            <td data-label="SIAPE"><?= htmlspecialchars($s->siape) ?></td>
                            <td data-label="Nome"><?= htmlspecialchars($s->nome . ' ' . $s->sobrenome) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($s->email) ?></td>
                            <td data-label="Setor"><?= htmlspecialchars($s->setor_admin) ?></td>
                            <td data-label="Cota PB"> <?= (int)($s->cota_pb_usada ?? 0) ?> / <?= (int)($s->cota_pb_total ?? 0) ?> </td>
                            <td data-label="Cota Colorida"> <?= (int)($s->cota_color_usada ?? 0) ?> / <?= (int)($s->cota_color_total ?? 0) ?> </td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <a href="../admin/form_servidor.php?siape=<?= htmlspecialchars($s->siape) ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a type="button" class="btn-action btn-redefinir" data-siape="<?= htmlspecialchars($s->siape) ?>" title="Redefinir Senha"><i class="fas fa-key"></i></a>
                                    
                                    <?php if ($s->siape !== $siape_logado && !(isset($s->is_super_admin) && $s->is_super_admin == 1)): ?>
                                        <button type="button" 
                                           class="btn-action btn-delete btn-excluir-servidor btn-exc" 
                                           data-siape="<?= htmlspecialchars($s->siape) ?>" 
                                           data-nome="<?= htmlspecialchars($s->nome . ' ' . $s->sobrenome) ?>" 
                                           title="Excluir Servidor">
                                           <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
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
        
        <div id="modal-redefinir" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Redefinir Senha do Servidor</h2>
                <form method="POST" action="redefinir_senha_servidor.php">
                    <input type="hidden" name="siape" id="siape-modal">
                    <label>Nova Senha <input type="password" name="nova_senha" required></label>
                    <button type="submit">Salvar Nova Senha</button>
                </form>
            </div>
        </div>

        <div id="modal-excluir" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Confirmar Exclusão</h2>
                <p>Você tem certeza que deseja excluir o servidor <strong id="nome-servidor-excluir"></strong>?</p>
                <p>Esta ação é irreversível.</p>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary btn-cancelar-exclusao">Cancelar</button>
                    <a href="#" id="btn-confirmar-exclusao" class="btn-danger">Sim, Excluir</a>
                </div>
            </div>
        </div>

    </main>
</div>
<script src="dashboard_coen.js"></script>
<?php include_once '../../includes/footer.php'; ?>
