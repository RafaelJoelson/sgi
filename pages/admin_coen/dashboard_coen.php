<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    header('Location: ../../index.php');
    exit;
}

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 5;
$offset = ($pagina - 1) * $limite;

// Condições de busca
$condicoes = [];
$params = [];
$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');

$base_sql = "FROM Servidor s LEFT JOIN CotaServidor cs ON s.siap = cs.siap";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') {
        $condicoes[] = "s.cpf = :valor";
    } elseif ($tipo_busca === 'siap') {
        $condicoes[] = "s.siap = :valor";
    }
    $params[':valor'] = $valor_busca;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Total de resultados
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()['total'] ?? 0;
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
$total_servidores = $conn->query("SELECT COUNT(*) AS total FROM Servidor")->fetch()['total'] ?? 0;

include_once '../../includes/header.php';
?>
<main class="container">
    <div class="dashboard-container">
        <aside>
            <section class="dashboard-header">
                <h1>Coordenação de Ensino (COEN)</h1>
            </section>
            <section class="dashboard-cards">
                <div class="card">Servidores Ativos: <?= $total_servidores ?></div>
            </section>
            <section class="dashboard-menu">
                <a class="btn-menu" href="gerenciar_cotas_servidor.php">Gerenciar Cotas de Servidor</a>
                <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>

            </section>
        </aside>
        <div class="responsive-table">
            <form method="GET" class="busca-form">
                <label for="tipo_busca">Buscar por:</label>
                <select name="tipo_busca" id="tipo_busca" required>
                    <option value="siap" <?= isset($_GET['tipo_busca']) && $_GET['tipo_busca'] === 'siap' ? 'selected' : '' ?>>SIAP</option>
                    <option value="cpf" <?= isset($_GET['tipo_busca']) && $_GET['tipo_busca'] === 'cpf' ? 'selected' : '' ?>>CPF</option>
                </select>
                <input type="text" name="valor_busca" placeholder="Digite o SIAP ou CPF" value="<?= htmlspecialchars($_GET['valor_busca'] ?? '') ?>" required>
                <button type="submit">Buscar</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>SIAP</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Setor</th>
                        <th>Cota PB</th>
                        <th>Cota Colorida</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servidores as $s): ?>
                        <tr>
                            <td data-label="SIAP"><?= $s->siap ?></td>
                            <td data-label="Nome"><?= $s->nome . ' ' . $s->sobrenome ?></td>
                            <td data-label="Email"><?= $s->email ?></td>
                            <td data-label="Setor"><?= $s->setor_admin ?></td>
                            <td data-label="Cota PB"> <?= $s->cota_pb_usada ?? 0 ?> / <?= $s->cota_pb_total ?? 0 ?> </td>
                            <td data-label="Cota Colorida"> <?= $s->cota_color_usada ?? 0 ?> / <?= $s->cota_color_total ?? 0 ?> </td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <a href="form_servidor.php?siap=<?= $s->siap ?>">Editar</a>
                                    <a href="#" class="btn-redefinir" data-siap="<?= $s->siap ?>">Redefinir Senha</a>
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
    </div>
    <?php if (!empty($_SESSION['mensagem'])): ?>
        <div id="toast-mensagem" class="mensagem-sucesso">
            <?= htmlspecialchars($_SESSION['mensagem']) ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    <div id="modal-redefinir" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Redefinir Senha do Servidor</h2>
            <form method="POST" action="redefinir_senha_servidor.php">
            <input type="hidden" name="siap" id="siap-modal">
            <label>Nova Senha
                <input type="password" name="nova_senha" required>
            </label>
            <button type="submit">Salvar Nova Senha</button>
            </form>
        </div>
    </div>
</main>
<script>
    document.querySelectorAll('.btn-redefinir').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const siap = this.getAttribute('data-siap');
        document.getElementById('siap-modal').value = siap;
        document.getElementById('modal-redefinir').style.display = 'block';
    });
    });

    document.querySelector('.modal .close').addEventListener('click', function () {
    document.getElementById('modal-redefinir').style.display = 'none';
    });

    window.addEventListener('click', function (e) {
    const modal = document.getElementById('modal-redefinir');
    if (e.target === modal) modal.style.display = 'none';
    });
</script>
<script>
window.addEventListener('DOMContentLoaded', () => {
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
