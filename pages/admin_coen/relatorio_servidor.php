<?php
// Relatório de Impressões por Servidor (COEN)
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
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

// --- 2. CONSULTA PRINCIPAL DO RELATÓRIO ---
$sql = "SELECT s.nome, s.sobrenome, si.data_criacao, si.colorida,
               (si.qtd_copias * si.qtd_paginas) as total_cotas
        FROM SolicitacaoImpressao si
        JOIN Servidor s ON si.cpf_solicitante = s.cpf
        WHERE si.tipo_solicitante = 'Servidor'
          AND si.data_criacao BETWEEN :inicio AND :fim
        ORDER BY s.nome, s.sobrenome, si.data_criacao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':inicio' => $data_ini . ' 00:00:00',
    ':fim' => $data_fim . ' 23:59:59'
]);
$relatorio = $stmt->fetchAll();

// --- 3. PROCESSAMENTO DOS DADOS PARA EXIBIÇÃO ---
$totais_servidor_pb = [];
$totais_servidor_color = [];
$total_geral_pb = 0;
$total_geral_color = 0;

foreach ($relatorio as $r) {
    $nome_completo = $r->nome . ' ' . $r->sobrenome;
    if (!isset($totais_servidor_pb[$nome_completo])) {
        $totais_servidor_pb[$nome_completo] = 0;
        $totais_servidor_color[$nome_completo] = 0;
    }
    if ($r->colorida) {
        $totais_servidor_color[$nome_completo] += $r->total_cotas;
        $total_geral_color += $r->total_cotas;
    } else {
        $totais_servidor_pb[$nome_completo] += $r->total_cotas;
        $total_geral_pb += $r->total_cotas;
    }
}
ksort($totais_servidor_pb); // Ordena os servidores por nome

// Define se a visualização é para impressão
$is_print_view = isset($_GET['imprimir']) && $_GET['imprimir'] == '1';

// --- 4. RENDERIZAÇÃO DO HTML ---
if ($is_print_view) :
// --- VISUALIZAÇÃO PARA IMPRESSÃO ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Relatório de Impressões por Servidor</title>
    <link rel="stylesheet" href="../../print_base.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.ico">
    <style>
        body { background: #fff; color: #222; font-family: Arial, sans-serif; }
        .print-header { text-align:center; margin-bottom:1.5rem; }
        .print-header img { height:50px; margin-bottom:0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; }
        th { background: #f2f2f2; }
        tfoot td { font-weight: bold; background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="print-header">
        <img src="../../img/logo-if-sjdr-nova-grafia-horizontal.png" alt="Logo IF"><br>
        <span style="font-size:1.3em;font-weight:bold;">Coordenação de Ensino</span><br>
        <span style="font-size:1.1em;">Relatório de Impressões por Servidor</span><br>
        <span style="font-size:1em;">Período: <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></span>
    </div>
<?php else: 
// --- VISUALIZAÇÃO DE TELA (DASHBOARD) ---
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_coen.css">
<main class="dashboard-layout">
    <aside class="dashboard-aside">
        <h1>Relatório de Impressões por Servidor</h1>
        <form method="GET" class="relatorio-form" id="form-relatorio">
            <label>Data Inicial:
                <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>" class="form-control">
            </label>
            <label>Data Final:
                <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" class="form-control">
            </label>
            <button type="submit" class="btn-menu">Filtrar</button>
        </form>
        <button type="button" class="btn-menu" onclick="imprimirRelatorio()">Imprimir</button>
        <a href="dashboard_coen.php" class="btn-back">Voltar</a>
    </aside>
<?php endif; ?>
    <main class="dashboard-main">
        <div class="responsive-table">
            <h2>Total por Servidor</h2>
            <table>
                <thead>
                    <tr>
                        <th>Servidor</th>
                        <th>Total P&B</th>
                        <th>Total Colorida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($totais_servidor_pb)): ?>
                        <tr><td colspan="3" style="text-align:center;">Nenhum registro encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($totais_servidor_pb as $nome => $total_pb): ?>
                        <tr>
                            <td><?= htmlspecialchars($nome) ?></td>
                            <td><?= $total_pb ?></td>
                            <td><?= $totais_servidor_color[$nome] ?? 0 ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td style="text-align:right;">Total Geral:</td>
                        <td><?= $total_geral_pb ?></td>
                        <td><?= $total_geral_color ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>
</main>
<?php if ($is_print_view): ?>
    <script>window.onload = () => window.print();</script>
</body>
</html>
<?php else: ?>
</div>
<script>
function imprimirRelatorio() {
    const form = document.getElementById('form-relatorio');
    if (form) {
        const params = new URLSearchParams(new FormData(form));
        params.append('imprimir', '1');
        window.open(`relatorio_servidor.php?${params.toString()}`, '_blank');
    }
}
</script>
<?php require_once '../../includes/footer.php'; ?>
<?php endif; ?>
