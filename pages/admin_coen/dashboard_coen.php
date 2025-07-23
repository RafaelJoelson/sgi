<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas administradores podem acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN' || empty($_SESSION['usuario']['is_admin'])) {
    header('Location: ../../index.php');
    exit;
}

// Pega os dados de permissão do admin logado
$siape_logado = $_SESSION['usuario']['id'];
$is_super_admin_logado = !empty($_SESSION['usuario']['is_super_admin']);

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
                   ORDER BY s.is_super_admin DESC, s.is_admin DESC, s.nome ASC 
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
<link rel="stylesheet" href="./css/dashboard_coen.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <section class="dashboard-header">
            <h1>Gerenciamento de Servidores</h1>
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
            <table>
                <thead>
                    <tr>
                        <th>SIAPE</th>
                        <th>Nome</th>
                        <th>Permissão</th>
                        <th>Setor Admin</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servidores as $s):
                        // Define as permissões para a linha atual
                        $is_row_super_admin = !empty($s->is_super_admin);
                        $is_row_admin = !empty($s->is_admin);
                        $is_self = ($s->siape === $siape_logado);

                        // Lógica para determinar quais botões mostrar
                        $can_edit = false;
                        $can_reset_password = false;
                        $can_delete = false;

                        if ($is_super_admin_logado) {
                            // SUPER ADMIN LOGADO: Pode fazer tudo, exceto em si mesmo ou noutros super admins.
                            if (!$is_row_super_admin && !$is_self) {
                                $can_edit = true;
                                $can_reset_password = true;
                                $can_delete = true;
                            }
                        } else { 
                            // ADMIN NORMAL LOGADO
                            if (!$is_row_super_admin) {
                                // Pode editar a si mesmo e outros admins normais.
                                $can_edit = true;
                            }
                            // CORREÇÃO: Pode redefinir a própria senha ou a de não-admins.
                            if (!$is_row_super_admin && ($is_self || !$is_row_admin)) {
                                $can_reset_password = true;
                            }
                            // Só pode excluir utilizadores que não são admins e não são ele mesmo.
                            if (!$is_row_admin && !$is_self) {
                                $can_delete = true;
                            }
                        }
                    ?>
                        <tr>
                            <td data-label="SIAPE"><?= htmlspecialchars($s->siape) ?></td>
                            <td data-label="Nome"><?= htmlspecialchars($s->nome . ' ' . $s->sobrenome) ?></td>
                            <td data-label="Permissão">
                                <?php if($is_row_super_admin): echo '<span class="badge-super-admin">Super Admin</span>'; ?>
                                <?php elseif($is_row_admin): echo '<span class="badge-admin">Admin</span>'; ?>
                                <?php else: echo '<span>Usuário</span>'; ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Setor"><?= htmlspecialchars($s->setor_admin) ?></td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <?php if ($can_edit): ?>
                                        <a href="../admin/form_servidor.php?siape=<?= htmlspecialchars($s->siape) ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                    
                                    <?php if ($can_reset_password): ?>
                                        <a type="button" class="btn-action btn-redefinir" data-siape="<?= htmlspecialchars($s->siape) ?>" title="Redefinir Senha"><i class="fas fa-key"></i></a>
                                    <?php endif; ?>
                                    
                                    <?php if ($can_delete): ?>
                                        <button type="button" class="btn-action btn-delete btn-excluir-servidor btn-exc" 
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
                <form method="POST" action="./functions/redefinir_senha_servidor.php">
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
<script src="./js/dashboard_coen.js?v=<?= ASSET_VERSION ?>"></script>
<?php include_once '../../includes/footer.php'; ?>
