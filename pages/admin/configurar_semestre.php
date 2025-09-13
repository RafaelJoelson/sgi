<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// Processa o formulário de adicionar/editar semestre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ano'])) {
    $ano = filter_input(INPUT_POST, 'ano', FILTER_VALIDATE_INT);
    $semestre_num = $_POST['semestre'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];

    // Validação dos dados
    if (!$ano || !$semestre_num || !$data_inicio || !$data_fim) {
        $_SESSION['mensagem_erro'] = "Todos os campos são obrigatórios.";
    } elseif (strtotime($data_fim) <= strtotime($data_inicio)) {
        $_SESSION['mensagem_erro'] = "A data de fim deve ser posterior à data de início.";
    } else {
        try {
            $conn->beginTransaction();

            $stmt_check = $conn->prepare("SELECT id, data_fim FROM SemestreLetivo WHERE ano = :ano AND semestre = :semestre");
            $stmt_check->execute([':ano' => $ano, ':semestre' => $semestre_num]);
            $semestre_existente = $stmt_check->fetch();

            if ($semestre_existente) {
                // MODO EDIÇÃO
                $antiga_data_fim = $semestre_existente->data_fim;
                
                // 1. Atualiza o semestre
                $stmt_update = $conn->prepare("UPDATE SemestreLetivo SET data_inicio = :inicio, data_fim = :fim WHERE id = :id");
                $stmt_update->execute([':inicio' => $data_inicio, ':fim' => $data_fim, ':id' => $semestre_existente->id]);
                
                // 2. Sincroniza a validade dos usuários, se a data de fim mudou
                $usuarios_afetados = 0;
                if ($antiga_data_fim !== $data_fim) {
                    $stmt_sync_usuarios = $conn->prepare("UPDATE Usuario SET data_fim_validade = :nova_data WHERE data_fim_validade = :antiga_data AND ativo = TRUE");
                    $stmt_sync_usuarios->execute([':nova_data' => $data_fim, ':antiga_data' => $antiga_data_fim]);
                    $usuarios_afetados = $stmt_sync_usuarios->rowCount();
                }

                $acao = "Atualizou semestre $ano/$semestre_num. Validade de $usuarios_afetados usuários ativos foi sincronizada.";

            } else {
                // MODO CADASTRO
                $stmt_insert = $conn->prepare("INSERT INTO SemestreLetivo (ano, semestre, data_inicio, data_fim) VALUES (:ano, :semestre, :inicio, :fim)");
                $stmt_insert->execute([':ano' => $ano, ':semestre' => $semestre_num, ':inicio' => $data_inicio, ':fim' => $data_fim]);
                $acao = "Cadastrou semestre $ano/$semestre_num: " . date('d/m/Y', strtotime($data_inicio)) . " a " . date('d/m/Y', strtotime($data_fim));
            }
            
            // Log da ação
            $usuario_log = $_SESSION['usuario']['nome'] . ' ' . ($_SESSION['usuario']['sobrenome'] ?? '') . ' (' . $_SESSION['usuario']['id'] . ')';
            $setor_log = $_SESSION['usuario']['setor_admin'];
            $stmt_log = $conn->prepare("INSERT INTO LogSemestreLetivo (usuario, setor, acao) VALUES (:usuario, :setor, :acao)");
            $stmt_log->execute([':usuario' => $usuario_log, ':setor' => $setor_log, ':acao' => $acao]);
            
            $conn->commit();
            $_SESSION['mensagem'] = 'Semestre salvo com sucesso!';

        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['mensagem_erro'] = 'Erro ao salvar semestre: ' . $e->getMessage();
        }
    }
    header('Location: configurar_semestre.php');
    exit;
}

// Lógica de exportação de log
if (isset($_GET['exportar_log']) && $_GET['exportar_log'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=log_semestre_letivo.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Usuário', 'Setor', 'Ação', 'Data']);
    $stmtExport = $conn->query("SELECT * FROM LogSemestreLetivo ORDER BY data DESC");
    while ($log = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $log['usuario'],
            $log['setor'],
            $log['acao'],
            date('d/m/Y H:i', strtotime($log['data']))
        ]);
    }
    fclose($output);
    exit;
}

// Buscar dados para a página
$stmt_semestres = $conn->query("SELECT * FROM SemestreLetivo ORDER BY ano DESC, semestre DESC");
$semestres = $stmt_semestres->fetchAll();
$stmt_logs = $conn->query("SELECT * FROM LogSemestreLetivo ORDER BY data DESC LIMIT 20");
$logs = $stmt_logs->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/configurar_semestre.css?v=<?= ASSET_VERSION ?>">

<div class="dashboard-layout">
    <aside class="dashboard-aside">
        <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <h1>Configurar Semestre Letivo</h1>
        
        <?php if (!empty($_SESSION['mensagem'])): ?>
            <div class="mensagem-sucesso"> <?= htmlspecialchars($_SESSION['mensagem']) ?> </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['mensagem_erro'])): ?>
            <div class="mensagem-erro"> <?= htmlspecialchars($_SESSION['mensagem_erro']) ?> </div>
            <?php unset($_SESSION['mensagem_erro']); ?>
        <?php endif; ?>
        
        <form method="POST" class="form-semestre" style="margin-bottom:1em;" id="form-semestre">
            <label>Ano <input type="number" name="ano" required min="2020" max="2100" value="<?= date('Y') ?>"></label>
            <label>Semestre 
                <select name="semestre" required>
                    <option value="1">1º Semestre</option>
                    <option value="2">2º Semestre</option>
                </select>
            </label>
            <label>Data de Início <input type="date" name="data_inicio" required></label>
            <label>Data de Fim <input type="date" name="data_fim" required></label>
            <button type="submit">Salvar Semestre</button>
        </form>

        <button type="button" id="btn-abrir-modal-cotas" class="btn-menu">Definir Cotas Padrão</button>
        <nav class="btn-container" aria-label="Ações">
            <a class="btn-back" href="javascript:history.back()">&larr; Voltar</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <h2>Semestres Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>Ano</th>
                    <th>Semestre</th>
                    <th>Início</th>
                    <th>Fim</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($semestres as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s->ano) ?></td>
                        <td><?= htmlspecialchars($s->semestre) ?></td>
                        <td><?= date('d/m/Y', strtotime($s->data_inicio)) ?></td>
                        <td><?= date('d/m/Y', strtotime($s->data_fim)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2 style="margin-top:2em;">Log de Alterações</h2>
        <a href="?exportar_log=csv" class="btn-cotas" style="margin-bottom:1em;display:inline-block;">Exportar Log (CSV)</a>
        <table>
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Setor</th>
                    <th>Ação</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log->usuario) ?></td>
                        <td><?= htmlspecialchars($log->setor) ?></td>
                        <td><?= htmlspecialchars($log->acao) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($log->data)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<!-- Modal para configurar cotas padrão -->
<div id="modal-cotas" class="modal">
    <div class="modal-content">
        <span class="close" id="close-modal-cotas">&times;</span>
        <h2>Definir Cotas Padrão</h2>
        <p>Estes valores serão aplicados no início de cada semestre letivo.</p>
        <form id="form-cotas">
            <div id="mensagem-modal-cotas" class="mensagem-feedback" style="display: none;"></div>
            <label>Cota Padrão para Alunos (por turma)
                <input type="number" name="cota_padrao_aluno" id="cota_padrao_aluno" required min="0">
            </label>
            <label>Cota Padrão P&B para Servidores
                <input type="number" name="cota_padrao_servidor_pb" id="cota_padrao_servidor_pb" required min="0">
            </label>
            <label>Cota Padrão Colorida para Servidores
                <input type="number" name="cota_padrao_servidor_color" id="cota_padrao_servidor_color" required min="0">
            </label>
            <button type="submit">Salvar Padrões de Cota</button>
        </form>
    </div>
</div>
<script src="./js/configurar_semestre.js?v=<?= ASSET_VERSION ?>"></script>
<?php include_once '../../includes/footer.php'; ?>
