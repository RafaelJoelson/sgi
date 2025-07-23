<?php
// Inclui o autoloader do Composer para carregar o dompdf
require_once '../../vendor/autoload.php';

// Referencia as classes do dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
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
$curso_id = isset($_GET['curso_id']) && $_GET['curso_id'] !== '' ? (int)$_GET['curso_id'] : null;
$periodo = isset($_GET['periodo']) && $_GET['periodo'] !== '' ? $_GET['periodo'] : null;
$turma_id = isset($_GET['turma_id']) && $_GET['turma_id'] !== '' ? (int)$_GET['turma_id'] : null;

$sql = "SELECT a.nome, a.sobrenome, c.sigla, c.nome_completo, t.periodo, t.id as turma_id, si.data_criacao, (si.qtd_copias * si.qtd_paginas) as total_cotas FROM SolicitacaoImpressao si JOIN Aluno a ON a.cpf = si.cpf_solicitante LEFT JOIN CotaAluno ca ON a.cota_id = ca.id LEFT JOIN Turma t ON ca.turma_id = t.id LEFT JOIN Curso c ON t.curso_id = c.id WHERE si.tipo_solicitante = 'Aluno' AND si.data_criacao BETWEEN :data_ini AND :data_fim";
$paramsFiltro = [':data_ini' => $data_ini . ' 00:00:00', ':data_fim' => $data_fim . ' 23:59:59'];
if ($curso_id) { $sql .= " AND c.id = :curso_id"; $paramsFiltro[':curso_id'] = $curso_id; }
if ($periodo) { $sql .= " AND t.periodo = :periodo"; $paramsFiltro[':periodo'] = $periodo; }
if ($turma_id) { $sql .= " AND t.id = :turma_id"; $paramsFiltro[':turma_id'] = $turma_id; }
$sql .= " ORDER BY c.nome_completo, t.periodo, a.nome, si.data_criacao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($paramsFiltro);
$relatorio = $stmt->fetchAll();

$total_geral = 0;
foreach ($relatorio as $r) {
    $total_geral += $r->total_cotas;
}

$cursos = $conn->query("SELECT id, sigla, nome_completo FROM Curso ORDER BY nome_completo")->fetchAll();
$periodos = $conn->query("SELECT DISTINCT periodo FROM Turma ORDER BY LENGTH(periodo), periodo ASC")->fetchAll();

// --- 2. LÓGICA DE GERAÇÃO DE ARQUIVOS ---

// Geração de PDF com Dompdf
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
        <title>Relatório de Impressões por Aluno</title>
        <style>
            body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; justify-content: space-between; align-items: center; }
            .logo_if_print { height: 50px; margin: 0 10px; }
            .sgi_logo_print { height: 50px; margin: 0 10px;}
            .header h1 { font-size: 16px; margin: 5px 0; }
            .header p { font-size: 12px; margin: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tfoot td { font-weight: bold; background-color: #f9f9f9; }
        </style>
    </head>
    <body>
        <div class="header">
            <?php if ($imagem_base64): ?>
                <img class="logo_if_print" src="<?= $imagem_base64 ?>" alt="Logo">
            <?php endif; ?>
            <?php if ($imagem2_base64): ?>
                <img class="sgi_logo_print"src="<?= $imagem2_base64 ?>" alt="Logo SGI">
            <?php endif; ?>
            <h1>Coordenação de Apoio ao Discente</h1>
            <p>Relatório de Impressões por Aluno</p>
            <p>Período: <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Aluno</th><th>Curso</th><th>Turma</th><th>Data da Solicitação</th><th>Qtd. Cotas Usadas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($relatorio)): ?>
                    <tr><td colspan="5" style="text-align:center;">Nenhum registro encontrado.</td></tr>
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
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Total geral:</td>
                    <td><?= $total_geral ?></td>
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
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("relatorio_alunos_" . date("Y-m-d") . ".pdf", ["Attachment" => true]);
    exit;
}

// RESTAURADO: Geração de página para impressão via navegador
if (isset($_GET['imprimir']) && $_GET['imprimir'] == '1') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Relatório de Impressões por Aluno</title>
        <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.ico">
        <script>window.onload = () => window.print();</script>
    </head>
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
    <body>
        <div style="text-align:center;margin-bottom:2em;">
            <img src="../../img/logo-if-sjdr-nova-grafia-horizontal.png" alt="Logo IFSudesteMG" style="height:60px;margin-bottom:0.5em;"><br>
            <span style="font-size:1.3em;font-weight:bold;">Coordenação de Apoio ao Discente</span><br>
            <span style="font-size:1.1em;">Relatório de Impressões por Aluno</span><br>
            <span style="font-size:1em;">Período: <?= htmlspecialchars(date('d/m/Y', strtotime($data_ini))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($data_fim))) ?></span><br>
            <span style="font-size:0.95em;color:#555;">Emitido em: <?= date('d/m/Y H:i') ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Aluno</th><th>Curso</th><th>Turma</th><th>Data da Solicitação</th><th>Qtd. Cotas Usadas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($relatorio)): ?>
                    <tr><td colspan="5" style="text-align:center;">Nenhum registro encontrado.</td></tr>
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
                        <td colspan="4" style="text-align:right;">Total geral:</td>
                        <td><?= $total_geral ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// --- 3. RENDERIZAÇÃO NORMAL DA PÁGINA ---
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/dashboard_cad.css?v=<?= ASSET_VERSION ?>">
<main class="dashboard-layout">
    <aside class="dashboard-aside-relatorio">
        <div class="container-principal">
        <?php
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <h1>Relatório de Impressões por Aluno</h1>
        <form method="GET" class="relatorio-form" id="form-relatorio">
            <div class="form-group">
                <label>Data Inicial: <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>" class="form-control"></label>
                <label>Data Final: <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" class="form-control"></label>
            </div>
            <hr>
            <label>Curso:
                <select name="curso_id">
                    <option value="">Todos</option>
                    <?php foreach ($cursos as $c): ?>
                        <option value="<?= $c->id ?>" <?= $curso_id == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->nome_completo) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Período:
                <select name="periodo">
                    <option value="">Todos</option>
                    <?php foreach ($periodos as $p): ?>
                        <option value="<?= htmlspecialchars($p->periodo) ?>" <?= $periodo == $p->periodo ? 'selected' : '' ?>><?= htmlspecialchars($p->periodo) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <hr>
            <button type="submit" class="btn-menu">Filtrar</button>
        </form>
        <div class="container-imprimir">
            <!-- Botão imprimir descontinuado -->
            <button type="button" class="btn-menu" onclick="gerarPDF()"><i class="fas fa-file-pdf"></i> Gerar PDF</button>
        </div>
        <div>
            <a class="btn-back" href="dashboard_cad.php">&larr; Voltar</a>
        </div>
        </div>
    </aside>
    <section class="dashboard-main">
        <div class="responsive-table">
            <table>
                <thead>
                    <tr style="font-weight:bold;background:#f3f3f3;">
                        <td colspan="4" style="text-align:right;">Total geral (todas as turmas listadas):</td>
                        <td><?= $total_geral ?></td>
                    </tr>
                    <tr>
                        <th>Aluno</th><th>Curso</th><th>Turma</th><th>Data da Solicitação</th><th>Qtd. Cotas Usadas</th>
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
</div>
<script>
function gerarPDF() {
    const form = document.getElementById('form-relatorio');
    if (form) {
        const params = new URLSearchParams(new FormData(form));
        params.append('gerar_pdf', '1');
        window.location.href = `relatorio_aluno.php?${params.toString()}`;
    }
}
</script>
<?php 
    require_once '../../includes/footer.php'; 
?>
