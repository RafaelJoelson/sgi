<?php
/**
 * Tarefas Diárias de Manutenção (Cron Job)
 *
 * Executa tarefas de manutenção como desativar usuários, arquivar
 * solicitações antigas e resetar cotas no início do semestre.
 */

// Define o fuso horário para garantir que as datas sejam consistentes
date_default_timezone_set('America/Sao_Paulo');

// Inclui o arquivo de configuração para conectar ao banco de dados
// O __DIR__ garante que o caminho seja sempre relativo à localização deste script.
require_once __DIR__ . '/../includes/config.php'; 

echo "--------------------------------------------------\n";
echo "Iniciando tarefas diárias de manutenção em: " . date('d/m/Y H:i:s') . "\n";
echo "--------------------------------------------------\n";

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
    echo "Iniciando limpeza de arquivos físicos antigos (> 15 dias) e arquivamento...\n";
    $data_limite = date('Y-m-d H:i:s', strtotime('-15 days'));

    // 2.1 Busca solicitações antigas com arquivos para apagar
    $stmt_busca = $conn->prepare(
        "SELECT id, arquivo_path FROM SolicitacaoImpressao 
         WHERE data_criacao < :data_limite 
           AND status IN ('Aceita', 'Rejeitada') 
           AND arquivada = FALSE 
           AND arquivo_path IS NOT NULL AND arquivo_path != ''"
    );
    $stmt_busca->execute([':data_limite' => $data_limite]);
    $solicitacoes_para_limpar = $stmt_busca->fetchAll(PDO::FETCH_OBJ);

    $arquivos_removidos = 0;
    $uploads_dir = realpath(__DIR__ . '/../uploads');

    if ($uploads_dir && !empty($solicitacoes_para_limpar)) {
        foreach ($solicitacoes_para_limpar as $sol) {
            $caminho_arquivo = $uploads_dir . '/' . $sol->arquivo_path;
            
            if (file_exists($caminho_arquivo)) {
                if (unlink($caminho_arquivo)) {
                    $arquivos_removidos++;
                }
            }
            
            // Atualiza o registro no banco para arquivar e remover o caminho do arquivo
            $stmt_update = $conn->prepare("UPDATE SolicitacaoImpressao SET arquivo_path = NULL, arquivada = TRUE WHERE id = :id");
            $stmt_update->execute([':id' => $sol->id]);
        }
    }
    echo "- " . $arquivos_removidos . " arquivo(s) físico(s) removido(s).\n";

    // 2.2 Arquiva solicitações de balcão antigas (que não têm arquivo)
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
        echo "=> INÍCIO DE SEMESTRE DETECTADO! Resetando todas as cotas com valores do banco de dados.\n";
        
        // Busca os valores padrão da nova tabela de configurações
        $configs_stmt = $conn->query("SELECT chave, valor FROM Configuracoes WHERE chave LIKE 'cota_padrao_%'");
        $configs = $configs_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $cota_aluno = $configs['cota_padrao_aluno'] ?? 600; // Fallback para 600
        $cota_servidor_pb = $configs['cota_padrao_servidor_pb'] ?? 1000; // Fallback para 1000
        $cota_servidor_color = $configs['cota_padrao_servidor_color'] ?? 100; // Fallback para 100

        // Reseta cotas de Alunos com o valor dinâmico
        $stmt_reset_alunos = $conn->prepare("UPDATE CotaAluno SET cota_total = ?, cota_usada = 0");
        $stmt_reset_alunos->execute([$cota_aluno]);
        echo "- Cotas de turmas de alunos resetadas para $cota_aluno.\n";

        // Reseta cotas de Servidores com os valores dinâmicos
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
    $mensagem_erro = "ERRO CRÍTICO NO CRON JOB: " . $e->getMessage() . " em " . date('d/m/Y H:i:s');
    error_log($mensagem_erro);
    echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    echo $mensagem_erro . "\n";
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
}
