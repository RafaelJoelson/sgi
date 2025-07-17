<?php
require_once '../../includes/config.php';
session_start();

// Permissão: Apenas administradores (CAD ou COEN) podem acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD', 'COEN'])) {
    header('Location: ../../index.php');
    exit;
}

$log_output = '';

// Verifica se o usuário clicou no botão para executar as tarefas
if (isset($_GET['executar']) && $_GET['executar'] == '1') {
    
    // Inicia o buffer de saída para capturar todos os 'echo' do script de tarefas
    ob_start();

    // --- INÍCIO DA LÓGICA DO TAREFAS_DIARIAS.PHP ---
    
    date_default_timezone_set('America/Sao_Paulo');
    echo "--------------------------------------------------\n";
    echo "Execução Manual de Tarefas de Manutenção em: " . date('d/m/Y H:i:s') . "\n";
    echo "--------------------------------------------------\n\n";

    try {
        // --- TAREFA 1: Desativar usuários com validade expirada ---
        echo "Verificando usuários expirados...\n";
        
        $stmt_desativar_alunos = $conn->prepare("UPDATE Aluno SET ativo = FALSE WHERE data_fim_validade IS NOT NULL AND data_fim_validade < CURDATE()");
        $stmt_desativar_alunos->execute();
        echo "- " . $stmt_desativar_alunos->rowCount() . " aluno(s) desativado(s).\n";

        $stmt_desativar_servidores = $conn->prepare("UPDATE Servidor SET ativo = FALSE WHERE data_fim_validade IS NOT NULL AND data_fim_validade < CURDATE() AND is_admin = FALSE");
        $stmt_desativar_servidores->execute();
        echo "- " . $stmt_desativar_servidores->rowCount() . " servidor(es) desativado(s).\n\n";

        // --- TAREFA 2: Arquivar solicitações antigas e limpar arquivos ---
        echo "Iniciando arquivamento de solicitações antigas (> 15 dias)...\n";
        $data_limite = date('Y-m-d H:i:s', strtotime('-15 days'));

        $stmt_busca = $conn->prepare(
            "SELECT id, arquivo_path FROM SolicitacaoImpressao 
             WHERE data_criacao < :data_limite AND status IN ('Aceita', 'Rejeitada') AND arquivada = FALSE AND arquivo_path IS NOT NULL AND arquivo_path != ''"
        );
        $stmt_busca->execute([':data_limite' => $data_limite]);
        $solicitacoes_para_limpar = $stmt_busca->fetchAll(PDO::FETCH_OBJ);

        $arquivos_removidos = 0;
        $uploads_dir = realpath(__DIR__ . '/../../uploads');

        if ($uploads_dir && !empty($solicitacoes_para_limpar)) {
            foreach ($solicitacoes_para_limpar as $sol) {
                $caminho_arquivo = $uploads_dir . '/' . $sol->arquivo_path;
                if (file_exists($caminho_arquivo)) {
                    if (@unlink($caminho_arquivo)) {
                        $arquivos_removidos++;
                    }
                }
                $stmt_update = $conn->prepare("UPDATE SolicitacaoImpressao SET arquivo_path = NULL, arquivada = TRUE WHERE id = :id");
                $stmt_update->execute([':id' => $sol->id]);
            }
        }
        echo "- " . $arquivos_removidos . " arquivo(s) físico(s) removido(s).\n";

        $stmt_arquivar_balcao = $conn->prepare(
            "UPDATE SolicitacaoImpressao SET arquivada = TRUE 
             WHERE data_criacao < :data_limite AND status IN ('Aceita', 'Rejeitada') AND arquivada = FALSE AND arquivo_path IS NULL"
        );
        $stmt_arquivar_balcao->execute([':data_limite' => $data_limite]);
        echo "- " . $stmt_arquivar_balcao->rowCount() . " solicitação(ões) de balcão arquivada(s).\n\n";

        // --- TAREFA 3: Resetar cotas no início de um novo semestre ---
        echo "Verificando início de semestre para reset de cotas...\n";
        $stmt_semestre = $conn->prepare("SELECT id FROM SemestreLetivo WHERE data_inicio = CURDATE() LIMIT 1");
        $stmt_semestre->execute();
        
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
            echo "- Cotas de servidores resetadas para $cota_servidor_pb (PB) e $cota_servidor_color (Colorida).\n";

        } else {
            echo "- Nenhum início de semestre hoje. Nenhuma cota foi resetada.\n";
        }

        echo "\n--------------------------------------------------\n";
        echo "Tarefas de manutenção concluídas com sucesso!\n";
        echo "--------------------------------------------------\n";

    } catch (PDOException $e) {
        $mensagem_erro = "ERRO CRÍTICO NA EXECUÇÃO: " . $e->getMessage() . " em " . date('d/m/Y H:i:s');
        error_log($mensagem_erro);
        echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
        echo $mensagem_erro . "\n";
        echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    }

    // --- FIM DA LÓGICA DO TAREFAS_DIARIAS.PHP ---
    
    // Captura tudo o que foi "echoed" para a variável $log_output
    $log_output = ob_get_clean();
}

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="../admin_cad/dashboard_cad.css"> <!-- Reutilizando um CSS de admin -->
<style>
    .cron-container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .cron-container h1 { text-align: center; color: #0056b3; }
    .cron-container p { text-align: center; color: #6c757d; margin-bottom: 2rem; }
    .btn-executar {
        display: block;
        width: 100%;
        padding: 1rem;
        font-size: 1.2rem;
        font-weight: bold;
        color: #fff;
        background-color: #28a745;
        border: none;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-executar:hover { background-color: #218838; }
    .log-output {
        background-color: #212529;
        color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 2rem;
        white-space: pre-wrap; /* Garante a quebra de linha */
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.9rem;
        max-height: 400px;
        overflow-y: auto;
    }
</style>

<div class="cron-container">
    <h1>Simulador de Tarefas Diárias (Cron Job)</h1>
    <p>Esta página executa as rotinas de manutenção do sistema manualmente. Use para testes ou em caso de falha do agendador automático.</p>
    
    <a href="?executar=1" class="btn-executar">
        <i class="fas fa-play-circle"></i> Executar Tarefas de Manutenção Agora
    </a>

    <?php if (!empty($log_output)): ?>
        <h2 style="margin-top: 2rem;">Resultados da Execução:</h2>
        <pre class="log-output"><?= htmlspecialchars($log_output) ?></pre>
    <?php endif; ?>

    <a href="javascript:history.back()" class="btn-back" style="margin-top: 2rem; display: inline-block;">Voltar</a>
</div>

<?php include_once '../../includes/footer.php'; ?>
