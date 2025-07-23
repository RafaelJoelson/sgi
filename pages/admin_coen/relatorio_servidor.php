<?php
// Inclui o autoloader do Composer para carregar o dompdf
require_once '../../vendor/autoload.php';

// Referencia as classes do dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD', 'COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// --- 1. LÓGICA DE FILTROS E CONSULTA ---
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT data_inicio, data_fim FROM SemestreLetivo WHERE :hoje BETWEEN data_inicio AND data_fim ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();
$default_data_ini = $semestre_vigente ? $semestre_vigente->data_inicio : date('Y-m-01');
$default_data_fim = $semestre_vigente ? $semestre_vigente->data_fim : date('Y-m-d');
$data_ini = $_GET['data_ini'] ?? $default_data_ini;
$data_fim = $_GET['data_fim'] ?? $default_data_fim;

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

// Processamento dos dados para os totais
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
ksort($totais_servidor_pb);

// --- 2. LÓGICA DE GERAÇÃO DE PDF ---
if (isset($_GET['gerar_pdf']) && $_GET['gerar_pdf'] == '1') {
    $caminho_imagem = realpath(__DIR__ . '/../../img/logo-if-sjdr-nova-grafia-horizontal.png');
    $caminho_imagem2 = realpath(__DIR__ . '/../../img/logo_sgi.png');
    $imagem_base64 = '';
    if ($caminho_imagem) {
        $tipo_imagem = pathinfo($caminho_imagem, PATHINFO_EXTENSION);
        $dados_imagem = file_get_contents($caminho_imagem);
        $imagem_base64 = 'data:image/' . $tipo_imagem . ';base64,' . base64_encode($dados_imagem);
    }
    $imagem2_base64 = '';
    if ($caminho_imagem2) {
        $tipo_imagem = pathinfo($caminho_imagem2, PATHINFO_EXTENSION);
        $dados_imagem = file_get_contents($caminho_imagem2);
        $imagem2_base64 = 'data:image/' . $tipo_imagem . ';base64,' . base64_encode($dados_imagem);
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Relatório de Impressões por Servidor</title>
        <style>
            body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; justify-content: space-between; align-items: center; }
            .logo_if_print { height: 50px; margin: 0 10px; }
            .sgi_logo_print { height: 50px; margin: 0 10px;}
            h1 { font-size: 16px; margin: 5px 0; }
            p { font-size: 12px; margin: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tfoot td { font-weight: bold; background-color: #f9f9f9; }
            .total-section { margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="header">
            <?php if ($imagem_base64): ?><img class="logo_if_print" src="<?= $imagem_base64 ?>" alt="Logo"><?php endif; ?>
            <?php if ($imagem2_base64): ?><img class="sgi_logo_print" src="<?= $imagem2_base64 ?>" alt="Logo SGI"><?php endif; ?>
            <h1>Coordenação de Ensino</h1>
            <p>Relatório de Impressões por Servidor</p>
            <p>Período: <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></p>
        </div>
        
        <h2>Total por Servidor</h2>
        <table>
            <thead>
                <tr><th>Servidor</th><th>Dia/Hora</th><th></th>Total P&B</th><th>Total Colorida</th></tr>
            </thead>
            <tbody>
                <?php if (empty($relatorio)): ?>
                    <tr><td colspan="4" style="text-align:center;">Nenhum registro encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($relatorio as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r->nome . ' ' . $r->sobrenome) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r->data_criacao)) ?></td>
                        <td><?= !$r->colorida ? $r->total_cotas : 0 ?></td>
                        <td><?= $r->colorida ? $r->total_cotas : 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align:right;">Total Geral:</td>
            <td><?= $total_geral_pb ?></td>
            <td><?= $total_geral_color ?></td>
        </tr>
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
    $dompdf->stream("relatorio_servidores_" . date("Y-m-d") . ".pdf", ["Attachment" => true]);
    exit;
}

// --- 3. RENDERIZAÇÃO NORMAL DA PÁGINA ---
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/dashboard_coen.css?v=<?= ASSET_VERSION ?>">
<main class="dashboard-layout">
    <aside class="dashboard-aside">
        <div class="container-principal">
        <?php if (isset($_SESSION['usuario'])) { gerar_migalhas(); } ?>
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
        <div class="container-imprimir">
            <button type="button" class="btn-menu" onclick="gerarPDF()"><i class="fas fa-file-pdf"></i> Gerar PDF</button>
        </div>
        <a href="dashboard_coen.php" class="btn-back">&larr; Voltar</a>
        </div>
    </aside>
    <main class="dashboard-main">
        <div class="responsive-table">
            <h2>Total por Servidor</h2>
            <table>
                <thead>
                    <tr>
                        <td colspan="2" style="text-align:right;">Total Geral:</td>
                        <td><?= $total_geral_pb ?></td>
                        <td><?= $total_geral_color ?></td>
                    </tr>
                    <tr>
                        <th>Servidor</th>
                        <th>Dia/Hora</th>
                        <th>Total P&B</th>
                        <th>Total Colorida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($relatorio)): ?>
                        <tr><td colspan="4" style="text-align:center;">Nenhum registro encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($relatorio as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r->nome . ' ' . $r->sobrenome) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r->data_criacao)) ?></td>
                            <td><?= !$r->colorida ? $r->total_cotas : 0 ?></td>
                            <td><?= $r->colorida ? $r->total_cotas : 0 ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:right;">Total Geral:</td>
                        <td><?= $total_geral_pb ?></td>
                        <td><?= $total_geral_color ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>
</main>
</div>
<script>
function gerarPDF() {
    const form = document.getElementById('form-relatorio');
    if (form) {
        const params = new URLSearchParams(new FormData(form));
        params.append('gerar_pdf', '1');
        window.location.href = `relatorio_servidor.php?${params.toString()}`;
    }
}
</script>
<?php require_once '../../includes/footer.php'; ?>
