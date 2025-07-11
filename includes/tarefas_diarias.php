<?php
/**
 * Tarefas Diárias de Manutenção (Cron Job)
 *
 * Este script deve ser executado uma vez por dia via Cron Job
 * para realizar as tarefas de manutenção do banco de dados que
 * eram anteriormente gerenciadas por MySQL Events.
 *
 * Comando de exemplo para o Cron Job:
 * php /home/seu_usuario/public_html/seu_projeto/backend/tarefas_diarias.php
 */

// Define o fuso horário para garantir que as datas sejam consistentes
date_default_timezone_set('America/Sao_Paulo');

// Inclui o arquivo de configuração para conectar ao banco de dados
require_once 'config.php';

// Inicia o log de execução
echo "--------------------------------------------------\n";
echo "Iniciando tarefas diárias de manutenção em: " . date('d/m/Y H:i:s') . "\n";
echo "--------------------------------------------------\n";

try {
    // --- TAREFA 1: Desativar usuários com validade expirada ---
    echo "Verificando usuários expirados...\n";
    
    // Desativa Alunos
    $stmt_desativar_alunos = $conn->prepare("UPDATE Aluno SET ativo = FALSE WHERE data_fim_validade IS NOT NULL AND data_fim_validade < CURDATE()");
    $stmt_desativar_alunos->execute();
    $alunos_desativados = $stmt_desativar_alunos->rowCount();
    echo "- " . $alunos_desativados . " aluno(s) desativado(s).\n";

    // Desativa Servidores (que não são administradores)
    $stmt_desativar_servidores = $conn->prepare("UPDATE Servidor SET ativo = FALSE WHERE data_fim_validade IS NOT NULL AND data_fim_validade < CURDATE() AND is_admin = FALSE");
    $stmt_desativar_servidores->execute();
    $servidores_desativados = $stmt_desativar_servidores->rowCount();
    echo "- " . $servidores_desativados . " servidor(es) desativado(s).\n\n";


    // --- TAREFA 2: Limpar solicitações de impressão antigas ---
    echo "Limpando solicitações antigas (mais de 15 dias)...\n";
    
    $stmt_limpar = $conn->prepare("DELETE FROM SolicitacaoImpressao WHERE data_criacao < NOW() - INTERVAL 15 DAY");
    $stmt_limpar->execute();
    $solicitacoes_removidas = $stmt_limpar->rowCount();
    echo "- " . $solicitacoes_removidas . " solicitação(ões) antiga(s) removida(s).\n\n";


    // --- TAREFA 3: Resetar cotas no início de um novo semestre ---
    echo "Verificando início de semestre para reset de cotas...\n";

    $stmt_semestre = $conn->prepare("SELECT id FROM SemestreLetivo WHERE data_inicio = CURDATE() LIMIT 1");
    $stmt_semestre->execute();
    
    if ($stmt_semestre->fetch()) {
        echo "=> INÍCIO DE SEMESTRE DETECTADO! Resetando todas as cotas.\n";
        
        // Reseta cotas de Alunos (por turma)
        $stmt_reset_alunos = $conn->prepare("UPDATE CotaAluno SET cota_total = 600, cota_usada = 0");
        $stmt_reset_alunos->execute();
        echo "- Cotas de turmas de alunos resetadas.\n";

        // Reseta cotas de Servidores
        $stmt_reset_servidores = $conn->prepare("UPDATE CotaServidor SET cota_pb_total = 1000, cota_pb_usada = 0, cota_color_total = 100, cota_color_usada = 0");
        $stmt_reset_servidores->execute();
        echo "- Cotas de servidores resetadas.\n";

    } else {
        echo "- Nenhum início de semestre hoje. Nenhuma cota foi resetada.\n";
    }

    echo "\n--------------------------------------------------\n";
    echo "Tarefas de manutenção concluídas com sucesso!\n";
    echo "--------------------------------------------------\n";

} catch (PDOException $e) {
    // Em caso de erro, registra a mensagem no log de erros do servidor para depuração
    $mensagem_erro = "ERRO CRÍTICO NO CRON JOB: " . $e->getMessage() . " em " . date('d/m/Y H:i:s');
    error_log($mensagem_erro);
    
    // Também exibe o erro para que possa ser visto nos logs do Cron Job
    echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    echo $mensagem_erro . "\n";
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
}
