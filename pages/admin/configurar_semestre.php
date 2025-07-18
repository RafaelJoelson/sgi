<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    header('Location: ../../index.php');
    exit;
}

// Cria tabela de log se não existir (boa prática manter)
$conn->exec("CREATE TABLE IF NOT EXISTS LogSemestreLetivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    setor VARCHAR(10) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    data DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
)");

// Processa o formulário de adicionar/editar semestre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ano'])) {
    $ano = filter_input(INPUT_POST, 'ano', FILTER_VALIDATE_INT);
    $semestre = $_POST['semestre'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];

    // Validação dos dados
    if (!$ano || !$semestre || !$data_inicio || !$data_fim) {
        $_SESSION['mensagem_erro'] = "Todos os campos são obrigatórios.";
    } elseif (strtotime($data_fim) <= strtotime($data_inicio)) {
        $_SESSION['mensagem_erro'] = "A data de fim deve ser posterior à data de início.";
    } else {
        try {
            $conn->beginTransaction();

            // Verifica se já existe um semestre para o mesmo ano/período
            $stmt_check = $conn->prepare("SELECT id FROM SemestreLetivo WHERE ano = :ano AND semestre = :semestre");
            $stmt_check->execute([':ano' => $ano, ':semestre' => $semestre]);
            $existe = $stmt_check->fetch();

            if ($existe) {
                // Atualiza o semestre existente
                $stmt_update = $conn->prepare("UPDATE SemestreLetivo SET data_inicio = :inicio, data_fim = :fim WHERE id = :id");
                $stmt_update->execute([':inicio' => $data_inicio, ':fim' => $data_fim, ':id' => $existe->id]);
                $acao = "Atualizou semestre $ano/$semestre para " . date('d/m/Y', strtotime($data_inicio)) . " a " . date('d/m/Y', strtotime($data_fim));
            } else {
                // Insere um novo semestre
                $stmt_insert = $conn->prepare("INSERT INTO SemestreLetivo (ano, semestre, data_inicio, data_fim) VALUES (:ano, :semestre, :inicio, :fim)");
                $stmt_insert->execute([':ano' => $ano, ':semestre' => $semestre, ':inicio' => $data_inicio, ':fim' => $data_fim]);
                $acao = "Cadastrou semestre $ano/$semestre: " . date('d/m/Y', strtotime($data_inicio)) . " a " . date('d/m/Y', strtotime($data_fim));
            }
            
            // Log da ação
            // CORREÇÃO: Acessando os dados da sessão como um array
            $usuario_log = $_SESSION['usuario']['nome'] . ' ' . ($_SESSION['usuario']['sobrenome'] ?? '') . ' (' . $_SESSION['usuario']['id'] . ')';
            $setor_log = $_SESSION['usuario']['setor_admin'];
            $stmt_log = $conn->prepare("INSERT INTO LogSemestreLetivo (usuario, setor, acao) VALUES (:usuario, :setor, :acao)");
            $stmt_log->execute([':usuario' => $usuario_log, ':setor' => $setor_log, ':acao' => $acao]);
            
            $conn->commit();
            $_SESSION['mensagem'] = 'Semestre salvo com sucesso!';

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
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
<link rel="stylesheet" href="configurar_semestre.css">

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

        <button type="button" id="btn-abrir-modal-cotas" class="btn-menu" style="background-color: #17a2b8;">Definir Cotas Padrão</button>
        
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lógica para exibir o toast de notificação
    const toast = document.getElementById('toast-mensagem');
    if (toast) {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // Lógica de confirmação do formulário de semestre
    const formSemestre = document.getElementById('form-semestre');
    if (formSemestre) {
        formSemestre.addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja salvar/alterar o semestre letivo? Esta ação impacta datas e cotas institucionais.')) {
                e.preventDefault();
            }
        });
    }

    // Lógica para o Modal de Cotas
    const modalCotas = document.getElementById('modal-cotas');
    const btnAbrirModal = document.getElementById('btn-abrir-modal-cotas');
    const btnFecharModal = document.getElementById('close-modal-cotas');
    const formCotas = document.getElementById('form-cotas');
    const mensagemModal = document.getElementById('mensagem-modal-cotas');

    btnAbrirModal.addEventListener('click', () => {
        fetch('obter_configuracoes.php')
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    document.getElementById('cota_padrao_aluno').value = data.dados.cota_padrao_aluno || 600;
                    document.getElementById('cota_padrao_servidor_pb').value = data.dados.cota_padrao_servidor_pb || 1000;
                    document.getElementById('cota_padrao_servidor_color').value = data.dados.cota_padrao_servidor_color || 100;
                    modalCotas.style.display = 'block';
                } else {
                    alert('Erro ao carregar configurações: ' + data.mensagem);
                }
            });
    });

    btnFecharModal.addEventListener('click', () => modalCotas.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modalCotas) modalCotas.style.display = 'none';
    });

    // Enviar formulário do modal e recarregar a página em caso de sucesso
    formCotas.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formCotas);

        fetch('salvar_configuracoes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Recarrega a página para que o PHP possa exibir o toast de sucesso
                window.location.reload();
            } else {
                // Se falhar, mostra o erro dentro do modal sem recarregar
                mensagemModal.textContent = data.mensagem;
                mensagemModal.className = 'mensagem-erro';
                mensagemModal.style.display = 'block';
            }
        });
    });
});
</script>
<?php include_once '../../includes/footer.php'; ?>
