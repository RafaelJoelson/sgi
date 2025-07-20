<?php
require_once '../../includes/config.php';
session_start();

// Permissão: Apenas administradores podem acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD', 'COEN'])) {
    header('Location: ../../index.php');
    exit;
}

$log_output = '';
$simulated_date_str = $_GET['simular_data'] ?? null;

// Verifica se o usuário clicou no botão para executar as tarefas
if ($simulated_date_str) {
    
    // Inicia o buffer de saída para capturar todos os 'echo'
    ob_start();

    // --- LÓGICA DE SIMULAÇÃO DE DATA ---
    $current_date_sql = ":simulated_date";
    $params = [':simulated_date' => $simulated_date_str];
    $current_date_php = $simulated_date_str;

    echo "--------------------------------------------------\n";
    echo "SIMULANDO TAREFAS PARA A DATA: " . date('d/m/Y', strtotime($current_date_php)) . "\n";
    echo "--------------------------------------------------\n\n";

    try {
        // --- TAREFA 1: Desativar usuários com validade expirada ---
        echo "Verificando usuários expirados...\n";
        
        $stmt_desativar_alunos = $conn->prepare("UPDATE Aluno SET ativo = FALSE, cargo = 'Nenhum' WHERE data_fim_validade IS NOT NULL AND data_fim_validade < $current_date_sql");
        $stmt_desativar_alunos->execute($params);
        echo "- " . $stmt_desativar_alunos->rowCount() . " aluno(s) desativado(s) e cargo(s) resetado(s).\n";

        $stmt_desativar_servidores = $conn->prepare("UPDATE Servidor SET ativo = FALSE WHERE data_fim_validade IS NOT NULL AND data_fim_validade < $current_date_sql AND is_admin = FALSE");
        $stmt_desativar_servidores->execute($params);
        echo "- " . $stmt_desativar_servidores->rowCount() . " servidor(es) desativado(s).\n\n";

        // --- TAREFA 2: Arquivar solicitações antigas e limpar arquivos ---
        echo "Iniciando arquivamento de solicitações antigas...\n";
        $data_limite = date('Y-m-d H:i:s', strtotime($current_date_php . ' -15 days'));

        $stmt_busca = $conn->prepare("SELECT id, arquivo_path FROM SolicitacaoImpressao WHERE data_criacao < :data_limite AND status IN ('Aceita', 'Rejeitada') AND arquivada = FALSE AND arquivo_path IS NOT NULL AND arquivo_path != ''");
        $stmt_busca->execute([':data_limite' => $data_limite]);
        $solicitacoes_para_limpar = $stmt_busca->fetchAll(PDO::FETCH_OBJ);

        $arquivos_removidos = 0;
        $uploads_dir = realpath(__DIR__ . '/../../uploads');

        if ($uploads_dir && !empty($solicitacoes_para_limpar)) {
            foreach ($solicitacoes_para_limpar as $sol) {
                $caminho_arquivo = $uploads_dir . '/' . $sol->arquivo_path;
                if (file_exists($caminho_arquivo)) {
                    if (@unlink($caminho_arquivo)) $arquivos_removidos++;
                }
                $stmt_update = $conn->prepare("UPDATE SolicitacaoImpressao SET arquivo_path = NULL, arquivada = TRUE WHERE id = :id");
                $stmt_update->execute([':id' => $sol->id]);
            }
        }
        echo "- " . $arquivos_removidos . " arquivo(s) físico(s) removido(s).\n";

        $stmt_arquivar_balcao = $conn->prepare("UPDATE SolicitacaoImpressao SET arquivada = TRUE WHERE data_criacao < :data_limite AND status IN ('Aceita', 'Rejeitada') AND arquivada = FALSE AND arquivo_path IS NULL");
        $stmt_arquivar_balcao->execute([':data_limite' => $data_limite]);
        echo "- " . $stmt_arquivar_balcao->rowCount() . " solicitação(ões) de balcão arquivada(s).\n\n";

        // --- TAREFA 3: Resetar cotas no início de um novo semestre ---
        echo "Verificando início de semestre para reset de cotas...\n";
        $stmt_semestre = $conn->prepare("SELECT id FROM SemestreLetivo WHERE data_inicio = $current_date_sql LIMIT 1");
        $stmt_semestre->execute($params);
        
        if ($stmt_semestre->fetch()) {
            echo "=> INÍCIO DE SEMESTRE DETECTADO! Resetando todas as cotas...\n";
            
            $configs_stmt = $conn->query("SELECT chave, valor FROM Configuracoes WHERE chave LIKE 'cota_padrao_%'");
            $configs = $configs_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $cota_aluno = $configs['cota_padrao_aluno'] ?? 600;
            $cota_servidor_pb = $configs['cota_padrao_servidor_pb'] ?? 1000;
            $cota_servidor_color = $configs['cota_padrao_servidor_color'] ?? 100;

            $stmt_reset_alunos = $conn->prepare("UPDATE CotaAluno SET cota_total = ?, cota_usada = 0");
            $stmt_reset_alunos->execute([$cota_aluno]);
            echo "- Cotas de alunos resetadas para $cota_aluno.\n";

            $stmt_reset_servidores = $conn->prepare("UPDATE CotaServidor SET cota_pb_total = ?, cota_pb_usada = 0, cota_color_total = ?, cota_color_usada = 0");
            $stmt_reset_servidores->execute([$cota_servidor_pb, $cota_servidor_color]);
            echo "- Cotas de servidores resetadas.\n";

        } else {
            echo "- Nenhum início de semestre hoje. Nenhuma cota foi resetada.\n";
        }

        echo "\n--------------------------------------------------\n";
        echo "Tarefas de manutenção concluídas com sucesso!\n";
        echo "--------------------------------------------------\n";

    } catch (PDOException $e) {
        $mensagem_erro = "ERRO CRÍTICO NA EXECUÇÃO: " . $e->getMessage();
        error_log($mensagem_erro);
        echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
        echo $mensagem_erro . "\n";
        echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    }

    $log_output = ob_get_clean();
}

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="../admin_cad/dashboard_cad.css">
<style>
    .cron-container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .cron-container h1 { text-align: center; color: #0056b3; }
    .cron-container p { text-align: center; color: #6c757d; margin-bottom: 2rem; }
    .simulation-form { display: flex; gap: 1rem; align-items: center; justify-content: center; margin-bottom: 2rem; }
    .log-output {
        background-color: #212529; color: #f8f9fa; padding: 1rem; border-radius: 8px;
        margin-top: 2rem; white-space: pre-wrap; font-family: 'Courier New', monospace;
        font-size: 0.9rem; max-height: 400px; overflow-y: auto;
    }
</style>

<div class="cron-container">
    <h1>Simulador de Tarefas Diárias (Cron Job)</h1>
    <p>Selecione uma data para simular a execução das rotinas de manutenção do sistema.</p>
    
    <form method="GET" class="simulation-form">
        <label for="simular_data">Simular para a data:</label>
        <input type="date" name="simular_data" id="simular_data" value="<?= htmlspecialchars($simulated_date_str ?? date('Y-m-d')) ?>" required>
        <button type="submit" class="btn-menu">Executar Tarefas</button>
    </form>

    <?php if (!empty($log_output)): ?>
        <h2 style="margin-top: 2rem;">Resultados da Execução:</h2>
        <pre class="log-output"><?= htmlspecialchars($log_output) ?></pre>
    <?php endif; ?>

    <a href="javascript:history.back()" class="btn-back" style="margin-top: 2rem; display: inline-block;">Voltar</a>
</div>

<?php include_once '../../includes/footer.php'; ?>
