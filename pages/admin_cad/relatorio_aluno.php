<?php
// Relatório de Impressões por Aluno (CAD)
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// --- 1. DEFINIÇÃO DOS FILTROS ---

// Buscar o semestre vigente para definir as datas padrão
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT data_inicio, data_fim FROM SemestreLetivo WHERE :hoje BETWEEN data_inicio AND data_fim ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();

// Define as datas padrão: ou do semestre vigente, ou do mês corrente como fallback
$default_data_ini = $semestre_vigente ? $semestre_vigente->data_inicio : date('Y-m-01');
$default_data_fim = $semestre_vigente ? $semestre_vigente->data_fim : date('Y-m-d');

// Usa as datas do GET se existirem, senão usa as datas padrão definidas acima
$data_ini = $_GET['data_ini'] ?? $default_data_ini;
$data_fim = $_GET['data_fim'] ?? $default_data_fim;

// Outros filtros
$curso_id = isset($_GET['curso_id']) && $_GET['curso_id'] !== '' ? (int)$_GET['curso_id'] : null;
$periodo = isset($_GET['periodo']) && $_GET['periodo'] !== '' ? $_GET['periodo'] : null;
$turma_id = isset($_GET['turma_id']) && $_GET['turma_id'] !== '' ? (int)$_GET['turma_id'] : null;

// --- 2. CONSULTA PRINCIPAL DO RELATÓRIO ---
$sql = "SELECT 
            a.nome, a.sobrenome, 
            c.sigla, c.nome_completo, 
            t.periodo, t.id as turma_id, 
            si.data_criacao, 
            (si.qtd_copias * si.qtd_paginas) as total_cotas
        FROM SolicitacaoImpressao si
        JOIN Aluno a ON a.cpf = si.cpf_solicitante
        LEFT JOIN CotaAluno ca ON a.cota_id = ca.id
        LEFT JOIN Turma t ON ca.turma_id = t.id
        LEFT JOIN Curso c ON t.curso_id = c.id
        WHERE si.tipo_solicitante = 'Aluno'
          AND si.data_criacao BETWEEN :data_ini AND :data_fim";

$paramsFiltro = [
    ':data_ini' => $data_ini . ' 00:00:00',
    ':data_fim' => $data_fim . ' 23:59:59'
];

// Adiciona filtros opcionais à consulta
if ($curso_id) {
    $sql .= " AND c.id = :curso_id";
    $paramsFiltro[':curso_id'] = $curso_id;
}
if ($periodo) {
    $sql .= " AND t.periodo = :periodo";
    $paramsFiltro[':periodo'] = $periodo;
}
if ($turma_id) {
    $sql .= " AND t.id = :turma_id";
    $paramsFiltro[':turma_id'] = $turma_id;
}

$sql .= " ORDER BY c.nome_completo, t.periodo, a.nome, si.data_criacao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($paramsFiltro);
$relatorio = $stmt->fetchAll();

// --- 3. PROCESSAMENTO DOS DADOS PARA EXIBIÇÃO ---
$totais_turma = [];
$total_geral = 0;
foreach ($relatorio as $r) {
    $tid = $r->turma_id;
    if (!isset($totais_turma[$tid])) {
        $totais_turma[$tid] = 0;
    }
    $totais_turma[$tid] += $r->total_cotas;
    $total_geral += $r->total_cotas;
}

// --- 4. BUSCA DE DADOS PARA OS MENUS DE FILTRO ---
$cursos = $conn->query("SELECT id, sigla, nome_completo FROM Curso ORDER BY nome_completo")->fetchAll();
$periodos = $conn->query("SELECT DISTINCT periodo FROM Turma ORDER BY LENGTH(periodo), periodo ASC")->fetchAll();

// Define se a visualização é para impressão
$is_print_view = isset($_GET['imprimir']) && $_GET['imprimir'] == '1';

// --- 5. RENDERIZAÇÃO DO HTML ---
if ($is_print_view) :
// --- VISUALIZAÇÃO PARA IMPRESSÃO ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Relatório de Impressões por Aluno</title>
    <link rel="stylesheet" href="../../print_base.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.ico">
</head>
<body>
    <div style="text-align:center;margin-bottom:2em;">
        <img src="../../img/logo-if-sjdr-nova-grafia-horizontal.png" alt="Logo IFSudesteMG" style="height:60px;margin-bottom:0.5em;"><br>
        <span style="font-size:1.3em;font-weight:bold;">Coordenação de Apoio ao Discente</span><br>
        <span style="font-size:1.1em;">Relatório de Impressões por Aluno</span><br>
        <span style="font-size:1em;">Período: <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></span><br>
        <span style="font-size:0.95em;color:#555;">Emitido em: <?= date('d/m/Y H:i') ?></span>
    </div>
<?php else: 
// --- VISUALIZAÇÃO DE TELA (DASHBOARD) ---
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_cad.css">
<main class="dashboard-layout">
    <aside class="dashboard-aside-relatorio">
        <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <h1>Relatório de Impressões por Aluno</h1>
        <!-- CORREÇÃO: A classe foi ajustada para 'relatorio-form' e o ID foi garantido -->
        <form method="GET" class="relatorio-form" id="form-relatorio">
            <label>Data Inicial:
                <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>" class="form-control">
            </label>
            <label>Data Final:
                <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" class="form-control">
            </label>
            <hr>
            <label>Curso:
                <select name="curso_id">
                    <option value="">Todos</option>
                    <?php foreach ($cursos as $c): ?>
                        <option value="<?= $c->id ?>" <?= $curso_id == $c->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->nome_completo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Período:
                <select name="periodo">
                    <option value="">Todos</option>
                    <?php foreach ($periodos as $p): ?>
                        <option value="<?= htmlspecialchars($p->periodo) ?>" <?= $periodo == $p->periodo ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p->periodo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn-menu">Filtrar</button>
            <button type="button" class="btn-menu" onclick="imprimirRelatorio()">Imprimir</button>
        </form>
        <div>
            <a class="btn-back" href="dashboard_cad.php">&larr; Voltar</a>
        </div>
    </aside>
<?php endif; ?>
    <section class="dashboard-main">
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Curso</th>
                        <th>Turma</th>
                        <th>Data da Solicitação</th>
                        <th>Qtd. Cotas Usadas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($relatorio)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#888;">Nenhum registro encontrado para os filtros selecionados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($relatorio as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r->nome . ' ' . $r->sobrenome) ?></td>
                            <td><?= htmlspecialchars($r->sigla) ?></td>
                            <td><?= htmlspecialchars($r->periodo) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r->data_criacao)) ?></td>
                            <td><?= $r->total_cotas ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight:bold;background:#f3f3f3;">
                            <td colspan="4" style="text-align:right;">Total geral (todas as turmas listadas):</td>
                            <td><?= $total_geral ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php if ($is_print_view): ?>
    <script>window.onload = () => window.print();</script>
</body>
</html>
<?php else: ?>
</div>
<script>
function imprimirRelatorio() {
    // CORREÇÃO: Usa o ID do formulário para garantir que ele seja encontrado
    const form = document.getElementById('form-relatorio');
    if (form) {
        const params = new URLSearchParams(new FormData(form));
        params.append('imprimir', '1');
        window.open(`relatorio_aluno.php?${params.toString()}`, '_blank');
    } else {
        alert('Erro: Formulário de relatório não encontrado.');
    }
}
</script>
<?php require_once '../../includes/footer.php'; ?>
<?php endif; ?>
