<?php
require_once '../../includes/config.php';
session_start();

// MUDANÇA: Permissão agora é para qualquer administrador (is_admin = true)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    header('Location: ../../index.php');
    exit;
}

// Pega o SIAPE do admin logado para a verificação de autoexclusão na tabela
$siape_logado = $_SESSION['usuario']['id'];

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 5;
$offset = ($pagina - 1) * $limite;

// Condições de busca
$condicoes = [];
$params = [];
$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');

$base_sql = "FROM Servidor s LEFT JOIN CotaServidor cs ON s.siape = cs.siape";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') {
        $condicoes[] = "s.cpf = :valor";
    } elseif ($tipo_busca === 'siape') {
        $condicoes[] = "s.siape = :valor";
    }
    $params[':valor'] = $valor_busca;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Total de resultados
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

// Consulta principal
$sql_servidores = "SELECT s.*, cs.cota_pb_total, cs.cota_pb_usada, cs.cota_color_total, cs.cota_color_usada 
                   " . $base_sql . " " . $where_clause . " 
                   ORDER BY s.nome ASC 
                   LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql_servidores);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$servidores = $stmt->fetchAll();

// Totais para os cards
$total_servidores = $conn->query("SELECT COUNT(*) AS total FROM Servidor")->fetch()->total ?? 0;

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_coen.css">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <section class="dashboard-header">
            <h1>Coordenação de Ensino</h1>
        </section>
        <section class="dashboard-cards">
            <div class="card">Total de Servidores: <?= $total_servidores ?></div>
        </section>
        <section class="dashboard-menu">
            <a class="btn-menu" href="gerenciar_cotas_servidor.php">Gerenciar Cotas de Servidor</a>
            <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>
            <a class="btn-menu" href="relatorio_servidor.php">Relatório de Impressões</a>
            <a class="btn-menu" href="../admin/form_servidor.php">Cadastrar Novo Servidor</a>
        </section>
    </aside>
    <main class="dashboard-main">
        <div class="responsive-table">
            <form method="GET" class="busca-form styled-busca-form">
                <label for="tipo_busca">Buscar por:</label>
                <select name="tipo_busca" id="tipo_busca" required>
                    <option value="siape" <?= ($tipo_busca === 'siape' ? 'selected' : '') ?>>SIAPE</option>
                    <option value="cpf" <?= ($tipo_busca === 'cpf' ? 'selected' : '') ?>>CPF</option>
                </select>
                <input type="text" name="valor_busca" placeholder="Digite o SIAPE ou CPF" value="<?= htmlspecialchars($valor_busca) ?>" required>
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
                                    <a href="../admin/form_servidor.php?siape=<?= htmlspecialchars($s->siape) ?>" class="btn-action btn-edit" title="Editar">Editar<i class="fas fa-edit"></i></a>
                                    <a type="button" class="btn-action btn-redefinir" data-siape="<?= htmlspecialchars($s->siape) ?>" title="Redefinir Senha">Redefinir Senha<i class="fas fa-key"></i></a>
                                    
                                    <?php if ($s->siape !== $siape_logado): // Impede que o admin se autoexclua ?>
                                        <button type="button" 
                                           class="btn-action btn-delete btn-excluir-servidor btn-exc" 
                                           data-siape="<?= htmlspecialchars($s->siape) ?>" 
                                           data-nome="<?= htmlspecialchars($s->nome . ' ' . $s->sobrenome) ?>" 
                                           title="Excluir Servidor">Excluir
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
                    <label>Nova Senha
                        <input type="password" name="nova_senha" required>
                    </label>
                    <button type="submit">Salvar Nova Senha</button>
                </form>
            </div>
        </div>

        <div id="modal-excluir" class="modal">
            <div class="modal-content">
                <span class="close" id="close-modal-excluir">&times;</span>
                <h2>Confirmar Exclusão</h2>
                <p>Você tem certeza que deseja excluir o servidor <strong id="nome-servidor-excluir"></strong>?</p>
                <p>Esta ação é irreversível e removerá todos os dados associados.</p>
                <div class="modal-actions">
                    <button type="button" id="btn-cancelar-exclusao" class="btn-secondary">Cancelar</button>
                    <a href="#" id="btn-confirmar-exclusao" class="btn-danger">Sim, Excluir</a>
                </div>
            </div>
        </div>

    </main>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mainContent = document.querySelector('.dashboard-main');

        mainContent.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-redefinir, .btn-excluir-servidor, .modal .close, #btn-cancelar-exclusao');
            
            if (!target) return;

            // Lógica para redefinir senha
            if (target.classList.contains('btn-redefinir')) {
                e.preventDefault();
                const siape = target.dataset.siape;
                document.getElementById('siape-modal').value = siape;
                document.getElementById('modal-redefinir').style.display = 'block';
            }

            // Lógica para excluir servidor
            if (target.classList.contains('btn-excluir-servidor')) {
                e.preventDefault();
                const siape = target.dataset.siape;
                const nome = target.dataset.nome;
                document.getElementById('nome-servidor-excluir').textContent = nome;
                document.getElementById('btn-confirmar-exclusao').href = `excluir_servidor.php?siape=${siape}`;
                document.getElementById('modal-excluir').style.display = 'block';
            }

            // Lógica para fechar modais
            if (target.classList.contains('close') || target.id === 'btn-cancelar-exclusao') {
                target.closest('.modal').style.display = 'none';
            }
        });

        // Fechar modal ao clicar fora
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });

        // Lógica para o toast de mensagem
        const toast = document.getElementById('toast-mensagem');
        if (toast) {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if(toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 600);
            }, 4000);
        }
    });
</script>
<?php include_once '../../includes/footer.php'; ?>
