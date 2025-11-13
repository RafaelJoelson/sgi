<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN' || empty($_SESSION['usuario']['is_admin'])) {
    header('Location: ../../index.php');
    exit;
}

$usuario_id_logado = $_SESSION['usuario']['id'];

// Parâmetros de paginação e busca
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 12;
$offset = ($pagina - 1) * $limite;

$condicoes = [];
$params = [];
$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');

$base_sql = "FROM Usuario u JOIN Servidor s ON u.id = s.usuario_id";

$condicoes[] = "u.tipo_usuario = 'servidor'";
$condicoes[] = "s.is_super_admin = 0"; // Não exibe os super admins (CAD/COEN)

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') $condicoes[] = "u.cpf = :valor";
    elseif ($tipo_busca === 'siape') $condicoes[] = "s.siape = :valor";
    $params[':valor'] = $valor_busca;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Consultas
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

$sql_servidores = "SELECT u.nome, u.sobrenome, u.ativo, s.siape, s.is_admin, s.setor_admin " . $base_sql . " " . $where_clause . " ORDER BY u.nome ASC LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql_servidores);
foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$servidores = $stmt->fetchAll();

$total_servidores_ativos = $conn->query("SELECT COUNT(*) AS total FROM Usuario u JOIN Servidor s ON u.id = s.usuario_id WHERE u.tipo_usuario = 'servidor' AND u.ativo = 1 AND s.is_super_admin = 0")->fetch()->total ?? 0;

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="../admin_cad/css/dashboard_cad.css?v=<?= ASSET_VERSION ?>"> <!-- Reutilizando CSS -->
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <section class="dashboard-header">
            <h1>Coordenação de Ensino</h1>
        </section>
        <section class="dashboard-cards">
            <div class="card">Servidores Ativos: <?= $total_servidores_ativos ?></div>
        </section>
        <section class="dashboard-menu">
            <a class="btn-menu" href="../admin/form_servidor.php">Cadastrar Novo Servidor</a>
            <a class="btn-menu" href="gerenciar_cotas_servidor.php">Gerenciar Cotas</a>
            <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>
            <a class="btn-menu" href="relatorio_servidor.php">Relatório de Impressões</a>
            <a class="btn-menu" href="../servidor/dashboard_servidor.php">Acessar Modo Solicitante</a>
        </section>
    </aside>
    <main class="dashboard-main">
        <div id="toast-notification-container"></div>
        <?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
            <div id="toast-mensagem" class="mensagem-sucesso" style="display: none;"><?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?></div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>
        
        <form method="GET" class="form-busca" style="margin-bottom: 1em;">
            <label>Tipo de Busca:
                <select name="tipo_busca" required>
                    <option value="" disabled <?= empty($tipo_busca) ? 'selected' : '' ?>>Selecione</option>
                    <option value="cpf" <?= $tipo_busca === 'cpf' ? 'selected' : '' ?>>CPF</option>
                    <option value="siape" <?= $tipo_busca === 'siape' ? 'selected' : '' ?>>SIAPE</option>
                </select>
            </label>
            <label>Valor:
                <input type="text" name="valor_busca" value="<?= htmlspecialchars($valor_busca) ?>" maxlength="11" placeholder="Digite o CPF ou SIAPE" required>
            </label>
            <button type="submit">Buscar</button>
            <?php if (!empty($tipo_busca) || !empty($valor_busca)): ?>
                <a href="?pagina=1" class="btn-limpar">Limpar Filtro</a>
            <?php endif; ?>
        </form>

        <div class="responsive-table">
            <table>
                <thead>
                    <tr><th>SIAPE</th><th>Nome</th><th>Admin?</th><th>Setor</th><th>Situação</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($servidores)): ?>
                        <tr><td colspan="6" class="text-center">Nenhum servidor encontrado.</td></tr>
                    <?php else: foreach ($servidores as $servidor): ?>
                        <tr>
                            <td data-label="SIAPE"><?= htmlspecialchars($servidor->siape) ?></td>
                            <td data-label="Nome"><?= htmlspecialchars($servidor->nome . ' ' . $servidor->sobrenome) ?></td>
                            <td data-label="Admin?"><?= $servidor->is_admin ? 'Sim' : 'Não' ?></td>
                            <td data-label="Setor"><?= htmlspecialchars($servidor->setor_admin) ?></td>
                            <td data-label="Situação"><span class="badge-<?= $servidor->ativo ? 'ativo' : 'inativo' ?>"><?= $servidor->ativo ? 'Ativo' : 'Inativo' ?></span></td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <a href="../admin/form_servidor.php?siape=<?= htmlspecialchars($servidor->siape) ?>" class="btn-action btn-edit" title="Editar/Renovar"><i class="fas fa-edit"></i></a>
                                    <a type="button" class="btn-action btn-redefinir btn-edit" data-siape="<?= htmlspecialchars($servidor->siape) ?>" title="Redefinir Senha"><i class="fas fa-key"></i></a>
                                    <button type="button" class="btn-action btn-delete btn-excluir-servidor btn-exc" 
                                            data-siape="<?= htmlspecialchars($servidor->siape) ?>" 
                                            data-nome="<?= htmlspecialchars($servidor->nome) ?>" 
                                            data-tipo="servidor" 
                                            data-url="../admin/functions/excluir_servidor.php?siape=<?= htmlspecialchars($servidor->siape) ?>"
                                            title="Excluir Servidor"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php if ($total_paginas > 1): ?>
                <nav class="paginacao">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a class="<?= $i === $pagina ? 'pagina-ativa' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
        
        <!-- Modais -->
        <div id="modal-redefinir-servidor" class="modal"><div class="modal-content"><span class="close">×</span><h2>Redefinir Senha do Servidor</h2><form method="POST" action="./functions/redefinir_senha_servidor.php"><input type="hidden" name="siape" id="siape-modal-servidor"><label>Nova Senha <input type="password" name="nova_senha" required></label><button type="submit">Salvar Nova Senha</button></form></div></div>
        <div id="modal-excluir" class="modal"><div class="modal-content"><span class="close">×</span><h2>Confirmar Exclusão</h2><p>Você tem certeza que deseja excluir <strong id="nome-item-excluir"></strong>?</p><p>Esta ação é irreversível.</p><div class="modal-actions"><button type="button" class="btn-secondary btn-cancelar-exclusao">Cancelar</button><a href="#" id="btn-confirmar-exclusao" class="btn-danger">Sim, Excluir</a></div></div></div>
    </main>
</div>
<script src="js/dashboard_coen.js?v=<?= ASSET_VERSION ?>"></script>
<?php include_once '../../includes/footer.php'; ?>