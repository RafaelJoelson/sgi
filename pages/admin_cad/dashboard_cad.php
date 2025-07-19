<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// Pega o SIAPE do admin logado para a verificação de autoexclusão na tabela
$siape_logado = $_SESSION['usuario']['id'];

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 5; // Itens por página
$offset = ($pagina - 1) * $limite;

// Condições de busca
$condicoes = [];
$params = [];
$tipo_busca = $_GET['tipo_busca'] ?? '';
$valor_busca = trim($_GET['valor_busca'] ?? '');
$filtro_turma = $_GET['turma'] ?? '';

// Montagem da query base com JOIN
$base_sql = "FROM Aluno a 
             LEFT JOIN CotaAluno ca ON a.cota_id = ca.id
             LEFT JOIN Turma t ON ca.turma_id = t.id
             LEFT JOIN Curso c ON t.curso_id = c.id";

if (!empty($tipo_busca) && !empty($valor_busca)) {
    if ($tipo_busca === 'cpf') {
        $condicoes[] = "a.cpf = :valor";
    } elseif ($tipo_busca === 'matricula') {
        $condicoes[] = "a.matricula = :valor";
    }
    $params[':valor'] = $valor_busca;
}
if (!empty($filtro_turma)) {
    $condicoes[] = "t.id = :turma_id";
    $params[':turma_id'] = $filtro_turma;
}

$where_clause = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

// Consultas de total e principal
$sql_count = "SELECT COUNT(*) AS total " . $base_sql . " " . $where_clause;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_resultados = $stmt_count->fetch()->total ?? 0;
$total_paginas = ceil($total_resultados / $limite);

$sql_alunos = "SELECT a.*, t.periodo, c.sigla, c.nome_completo " . $base_sql . " " . $where_clause . " ORDER BY a.nome ASC LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql_alunos);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$alunos = $stmt->fetchAll();

// Dados para os cards e filtros
$total_turmas = $conn->query("SELECT COUNT(DISTINCT turma_id) AS total FROM CotaAluno")->fetch()->total ?? 0;
$total_alunos = $conn->query("SELECT COUNT(*) AS total FROM Aluno")->fetch()->total ?? 0;
$stmt_turmas = $conn->query("SELECT t.id, t.periodo, c.sigla, c.nome_completo FROM Turma t JOIN Curso c ON t.curso_id = c.id ORDER BY c.nome_completo ASC, t.periodo ASC");
$turmas_disponiveis = $stmt_turmas->fetchAll();


include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_cad.css">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <section class="dashboard-header">
            <h1>Coordenação de Apoio ao Discente</h1>
        </section>
        <section class="dashboard-cards">
            <div class="card">Turmas Ativas: <?= $total_turmas ?></div>
            <div class="card">Alunos Ativos: <?= $total_alunos ?></div>
        </section>
        <section class="dashboard-menu">
            <a class="btn-menu" href="form_aluno.php">Cadastrar novo aluno</a>
            <a class="btn-menu" href="#" id="btn-gerenciar-servidores">Gerenciar Servidores (CAD)</a>
            <a class="btn-menu" href="gerenciar_cotas.php">Gerenciar Cotas</a>
            <a class="btn-menu" href="gerenciar_turmas.php">Gerenciar Turmas</a>
            <a class="btn-menu" href="../admin/configurar_semestre.php">Configurar Semestre Letivo</a>
            <a class="btn-menu" href="relatorio_aluno.php">Relatório de Impressões</a>
            <a class="btn-menu" href="../servidor/dashboard_servidor.php">Acessar Modo Solicitante</a>
            <a class="btn-menu" href="../../includes/tarefas_diarias.php">Simular Cron</a>
        </section>
    </aside>
    <main class="dashboard-main">
        
        <div class="responsive-table">
            
            <form method="GET" class="busca-form styled-busca-form">
                <div class="form-group">
                    <label for="tipo_busca">Buscar por:</label>
                    <select name="tipo_busca" id="tipo_busca">
                        <option value="cpf" <?= ($tipo_busca === 'cpf' ? 'selected' : '') ?>>CPF</option>
                        <option value="matricula" <?= ($tipo_busca === 'matricula' ? 'selected' : '') ?>>Matrícula</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="valor_busca">Valor:</label>
                    <input type="text" id="valor_busca" name="valor_busca" placeholder="Digite o valor..." value="<?= htmlspecialchars($valor_busca) ?>">
                </div>
                <div class="form-group filter">
                    <label for="turma">Filtrar por Turma:</label>
                    <select name="turma" id="turma">
                        <option value="">Todas as turmas</option>
                        <?php foreach ($turmas_disponiveis as $turma): ?>
                            <option value="<?= $turma->id ?>" <?= ($filtro_turma == $turma->id ? 'selected' : '') ?>>
                                <?= htmlspecialchars($turma->nome_completo . ' - ' . $turma->periodo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filtrar">Buscar</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Turma</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunos as $aluno): ?>
                        <tr>
                            <td data-label="Matrícula"><?= htmlspecialchars($aluno->matricula) ?></td>
                            <td data-label="Nome"><?= htmlspecialchars($aluno->nome) ?> <?= htmlspecialchars($aluno->sobrenome) ?></td>
                            <td data-label="Cargo"><?= htmlspecialchars($aluno->cargo) ?></td>
                            <td data-label="Turma" title="<?= htmlspecialchars($aluno->nome_completo . ' - ' . $aluno->periodo) ?>"><?= htmlspecialchars($aluno->sigla . ' - ' . $aluno->periodo) ?></td>
                            <td data-label="Ações">
                                <div class="action-buttons">
                                    <a href="form_aluno.php?matricula=<?= $aluno->matricula ?>" title="Editar/Renovar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn-redefinir" data-matricula="<?= $aluno->matricula ?>" title="Redefinir Senha">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <a class="btn-exc" href="excluir_aluno.php?matricula=<?= $aluno->matricula ?>"
                                    onclick="return confirm('Tem certeza que deseja excluir o aluno <?= htmlspecialchars($aluno->nome) ?>?')"
                                    title="Excluir">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
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
        
        <div id="modal-redefinir-aluno" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Redefinir Senha do Aluno</h2>
                <form method="POST" action="redefinir_senha.php">
                    <input type="hidden" name="matricula" id="matricula-modal-aluno">
                    <label>Nova Senha <input type="password" name="nova_senha" required></label>
                    <button type="submit">Salvar Nova Senha</button>
                </form>
            </div>
        </div>

        <div id="modal-servidores" class="modal">
          <div class="modal-content" style="max-width:900px;width:98%;">
            <span class="close">&times;</span>
            <h2>Servidores do Setor CAD</h2>
            <button onclick="window.location.href='../admin/form_servidor.php'" class="btn-menu" style="margin-bottom:1em;">Novo Servidor</button>
            <div id="tabela-servidores-cad">Carregando...</div>
          </div>
        </div>

        <div id="modal-excluir" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Confirmar Exclusão</h2>
                <p>Você tem certeza que deseja excluir <strong id="nome-item-excluir"></strong>?</p>
                <p>Esta ação é irreversível.</p>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary btn-cancelar-exclusao">Cancelar</button>
                    <a href="#" id="btn-confirmar-exclusao" class="btn-danger">Sim, Excluir</a>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const siapeLogado = '<?= htmlspecialchars($siape_logado) ?>';
    const mainContent = document.querySelector('.dashboard-layout');

    mainContent.addEventListener('click', function(e) {
        // MUDANÇA: O seletor agora inclui o link 'a.btn-redefinir'
        const target = e.target.closest('button.btn-action, a.btn-redefinir, .close, .btn-cancelar-exclusao, #btn-gerenciar-servidores');
        if (!target) return;

        // Gerenciar Servidores (abrir modal)
        if (target.id === 'btn-gerenciar-servidores') {
            e.preventDefault();
            document.getElementById('modal-servidores').style.display = 'block';
            carregarServidoresCAD();
        }

        // Redefinir senha de aluno (abrir modal)
        if (target.classList.contains('btn-redefinir')) {
            e.preventDefault();
            // MUDANÇA: Usa 'data-matricula' do link de texto
            const matricula = target.dataset.matricula;
            document.getElementById('matricula-modal-aluno').value = matricula;
            document.getElementById('modal-redefinir-aluno').style.display = 'block';
        }

        // Excluir (abrir modal de confirmação para servidores no modal)
        if (target.classList.contains('btn-excluir')) {
            e.preventDefault();
            const id = target.dataset.id;
            const nome = target.dataset.nome;
            const tipo = target.dataset.tipo;
            
            document.getElementById('nome-item-excluir').textContent = `o ${tipo} ${nome}`;
            const linkConfirmar = `../admin/excluir_servidor.php?siape=${id}`;
            document.getElementById('btn-confirmar-exclusao').href = linkConfirmar;
            document.getElementById('modal-excluir').style.display = 'block';
        }

        // Fechar qualquer modal
        if (target.classList.contains('close') || target.classList.contains('btn-cancelar-exclusao')) {
            e.preventDefault();
            target.closest('.modal').style.display = 'none';
        }
    });

    // Fechar modal ao clicar fora
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    // --- FUNÇÃO PARA CARREGAR SERVIDORES ---
    function carregarServidoresCAD() {
        fetch('../admin/listar_servidores_cad.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table style="width:100%;"><thead><tr><th>SIAPE</th><th>Nome</th><th>Email</th><th>Ações</th></tr></thead><tbody>';
                if (data.length === 0) {
                    html += '<tr><td colspan="4">Nenhum servidor CAD encontrado.</td></tr>';
                } else {
                    data.forEach(s => {
                        let botaoExcluir = '';
                        if (s.siape !== siapeLogado) {
                            // O botão de excluir aqui continua usando o modal, pois está dentro de um conteúdo dinâmico
                            botaoExcluir = `<div class="action-buttons">
                                                <button type="button" class="btn-action btn-delete btn-exc" 
                                                data-id="${s.siape}" 
                                                data-nome="${s.nome} ${s.sobrenome}" 
                                                data-tipo="servidor"
                                                title="Excluir Servidor">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            </div>`;
                        }
                        html += `<tr>
                            <td>${s.siape}</td>
                            <td>${s.nome} ${s.sobrenome}</td>
                            <td>${s.email}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="../admin/form_servidor.php?siape=${s.siape}" class="btn-action btn-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    ${botaoExcluir}
                                </div>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table>';
                document.getElementById('tabela-servidores-cad').innerHTML = html;
            });
    }
});
</script>
<?php include_once '../../includes/footer.php'; ?>
