<?php
// Relatório do Reprográfo
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    header('Location: ../../reprografia.php');
    exit;
}

// --- 1. LÓGICA DE FILTROS E CONSULTA ---

// Define o período padrão para o mês corrente
$data_ini = $_GET['data_ini'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status_filtro = $_GET['status'] ?? '';
$tipo_filtro = $_GET['tipo'] ?? '';

// Monta a cláusula WHERE da consulta dinamicamente
$where_conditions = "WHERE s.data_criacao BETWEEN :data_ini AND :data_fim";
$params = [
    ':data_ini' => $data_ini . ' 00:00:00',
    ':data_fim' => $data_fim . ' 23:59:59'
];

if ($status_filtro !== '') {
    $where_conditions .= " AND s.status = :status";
    $params[':status'] = $status_filtro;
}
if ($tipo_filtro !== '') {
    $where_conditions .= " AND s.tipo_solicitante = :tipo";
    $params[':tipo'] = $tipo_filtro;
}

// Consulta que agrupa os totais por tipo de solicitante e cor
$sql = "SELECT tipo_solicitante, colorida, SUM(qtd_paginas * qtd_copias) AS total_paginas 
        FROM SolicitacaoImpressao s 
        $where_conditions 
        GROUP BY tipo_solicitante, colorida 
        ORDER BY tipo_solicitante, colorida";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$dados_relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processa os dados para facilitar a exibição dos totais
$totais = [
    'pb' => 0,
    'colorida' => 0
];
foreach ($dados_relatorio as $dado) {
    if ((int)$dado['colorida'] === 0) {
        $totais['pb'] += (int)$dado['total_paginas'];
    } else {
        $totais['colorida'] += (int)$dado['total_paginas'];
    }
}

// Define se a visualização é para impressão
$is_print_view = isset($_GET['imprimir']) && $_GET['imprimir'] == '1';

// --- 2. LÓGICA DE APRESENTAÇÃO (HTML) ---

if ($is_print_view) :
// --- VISUALIZAÇÃO PARA IMPRESSÃO ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Relatório de Impressões - <?= date('d/m/Y') ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../print_base.css">
    <style>
        body { background-color: #fff; }
        .print-header { text-align: center; margin-bottom: 2rem; }
        .print-header img { height: 60px; margin-bottom: 1rem; }
        @media print { body { -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="print-header">
            <img src="../../img/logo-if-sjdr-nova-grafia-horizontal.png" alt="Logo IFSudesteMG">
            <h3>Reprografia - Relatório de Impressões</h3>
            <img src="../../img/logo_reprografia.png" alt="">
            <p>Período de <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></p>
            <small class="text-muted">Emitido em: <?= date('d/m/Y H:i') ?></small>
        </div>
<?php else: 
// --- VISUALIZAÇÃO DE TELA (DASHBOARD) ---
require_once '../../includes/header.php'; 
?>
<link rel="stylesheet" href="dashboard_relatorio_reprografia.css">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <h3>Filtros do Relatório</h3>
        <form method="get" class="relatorios-form">
            <div class="form-group">
                <label for="data_ini">Data inicial</label>
                <input type="date" id="data_ini" name="data_ini" class="form-control" value="<?= htmlspecialchars($data_ini) ?>">
            </div>
            <div class="form-group">
                <label for="data_fim">Data final</label>
                <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
        </form>
        <hr>
        <div class="container-imprimir">
            <button type="button" class="btn btn-info btn-block relatorios-imprimir" onclick="imprimirRelatorio()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
        <nav class="btn-container mt-3">
            <a class="btn btn-secondary btn-back" href="dashboard_reprografia.php">&larr; Voltar ao Painel</a>
        </nav>
    </aside>
    <main class="dashboard-main">
<?php endif; ?>

        <!-- TABELA DE DADOS (Comum para ambas as visualizações) -->
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Tipo de Usuário</th>
                    <th>Cor da Impressão</th>
                    <th>Total de Páginas Impressas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dados_relatorio)): ?>
                    <tr><td colspan="3" class="text-center">Nenhum dado encontrado para os filtros selecionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($dados_relatorio as $dado): ?>
                        <tr>
                            <td><?= htmlspecialchars($dado['tipo_solicitante']) ?></td>
                            <td><?= (int)$dado['colorida'] === 1 ? 'Colorida' : 'Preto e Branco' ?></td>
                            <td><?= (int)$dado['total_paginas'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Linhas de Totalização -->
                    <tr class="table-secondary font-weight-bold">
                        <td colspan="2">Total de Páginas P&B (Alunos + Servidores)</td>
                        <td><?= $totais['pb'] ?></td>
                    </tr>
                    <tr class="table-info font-weight-bold">
                        <td colspan="2">Total de Páginas Coloridas (Servidores)</td>
                        <td><?= $totais['colorida'] ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

<?php if ($is_print_view): ?>
    </div>
    <script>window.onload = () => window.print();</script>
</body>
</html>
<?php else: ?>
    </main>
</div>
<script>
function imprimirRelatorio() {
    const form = document.querySelector('.relatorios-form');
    const params = new URLSearchParams(new FormData(form));
    params.append('imprimir', '1');
    window.open(`relatorio_reprografia.php?${params.toString()}`, '_blank');
}
</script>
<?php require_once '../../includes/footer.php'; ?>
<?php endif; ?>
