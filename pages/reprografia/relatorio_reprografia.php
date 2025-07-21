<?php
// Inclui o autoloader do Composer para carregar o dompdf
require_once '../../vendor/autoload.php';

// Referencia as classes do dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

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
    'colorida' => 0,
    'aluno_pb' => 0,
    'servidor_pb' => 0,
    'servidor_color' => 0
];
foreach ($dados_relatorio as $dado) {
    if ((int)$dado['colorida'] === 0) {
        $totais['pb'] += (int)$dado['total_paginas'];
        if ($dado['tipo_solicitante'] === 'Aluno') {
            $totais['aluno_pb'] = (int)$dado['total_paginas'];
        } else {
            $totais['servidor_pb'] = (int)$dado['total_paginas'];
        }
    } else {
        $totais['colorida'] += (int)$dado['total_paginas'];
        $totais['servidor_color'] = (int)$dado['total_paginas'];
    }
}

// --- 2. LÓGICA DE GERAÇÃO DE PDF ---
if (isset($_GET['gerar_pdf']) && $_GET['gerar_pdf'] == '1') {
    $caminho_logo_if = realpath(__DIR__ . '/../../img/logo-if-sjdr-nova-grafia-horizontal.png');
    $caminho_logo2_if = realpath(__DIR__ . '/../../img/logo_reprografia.png'); // Ajuste conforme necessário
    $logo_if_base64 = '';
    if ($caminho_logo_if) {
        $tipo = pathinfo($caminho_logo_if, PATHINFO_EXTENSION);
        $dados = file_get_contents($caminho_logo_if);
        $logo_if_base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($dados);
    }
    $logo2_if_base64 = '';
    if ($caminho_logo2_if) {
        $tipo = pathinfo($caminho_logo2_if, PATHINFO_EXTENSION);
        $dados = file_get_contents($caminho_logo2_if);
        $logo2_if_base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($dados);
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Relatório de Impressões - Reprografia</title>
        <style>
            body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; justify-content: space-between; }
            .header img { height: 50px; margin: 0 10px; }
            h1 { font-size: 16px; margin: 5px 0; }
            p { font-size: 12px; margin: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tfoot td { font-weight: bold; background-color: #f9f9f9; }
        </style>
    </head>
    <body>
        <div class="header">
            <?php if ($logo_if_base64): ?><img src="<?= $logo_if_base64 ?>" alt="Logo"><?php endif; ?>
            <?php if ($logo2_if_base64): ?><img src="<?= $logo2_if_base64 ?>" alt="Logo2"><?php endif; ?>
            <h1>Reprografia - Relatório Geral de Impressões</h1>
            <p>Período: <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Total de Páginas Impressas</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Alunos (P&B)</td><td><?= $totais['aluno_pb'] ?></td></tr>
                <tr><td>Servidores (P&B)</td><td><?= $totais['servidor_pb'] ?></td></tr>
                <tr><td>Servidores (Colorida)</td><td><?= $totais['servidor_color'] ?></td></tr>
            </tbody>
            <tfoot>
                <tr><td>Total Geral P&B</td><td><?= $totais['pb'] ?></td></tr>
                <tr><td>Total Geral Colorida</td><td><?= $totais['colorida'] ?></td></tr>
                <tr style="font-size: 1.1em;"><td><strong>Total Geral (P&B + Colorida)</strong></td><td><strong><?= $totais['pb'] + $totais['colorida'] ?></strong></td></tr>
            </tfoot>
        </table>
        <div style="margin-top:30px;font-size:11px;color:#555;text-align:right;">
            Relatório gerado em: <?= date('d/m/Y H:i') ?><br>
            Usuário: <?= htmlspecialchars($_SESSION['usuario']['nome'] . ' ' . ($_SESSION['usuario']['sobrenome'] ?? '')) ?>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("relatorio_reprografia_" . date("Y-m-d") . ".pdf", ["Attachment" => true]);
    exit;
}

// --- 3. RENDERIZAÇÃO NORMAL DA PÁGINA ---
require_once '../../includes/header.php'; 
?>
<link rel="stylesheet" href="dashboard_relatorio_reprografia.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <div class="container-principal">
        <?php if (isset($_SESSION['usuario'])) { gerar_migalhas(); } ?>
        <h3>Filtros do Relatório</h3>
        <form method="get" class="relatorios-form" id="form-relatorio">
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
            <button type="button" class="btn btn-info btn-block relatorios-imprimir" onclick="gerarPDF()">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </button>
        </div>
        <nav class="btn-container mt-3">
            <a class="btn btn-secondary btn-back" href="dashboard_reprografia.php">&larr; Voltar ao Painel</a>
        </nav>
        </div>
    </aside>
    <main class="dashboard-main">
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
    </main>
</div>
<script>
function gerarPDF() {
    const form = document.getElementById('form-relatorio');
    if (form) {
        const params = new URLSearchParams(new FormData(form));
        params.append('gerar_pdf', '1');
        window.location.href = `relatorio_reprografia.php?${params.toString()}`;
    }
}
</script>
<?php require_once '../../includes/footer.php'; ?>
