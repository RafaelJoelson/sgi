<?php
require_once '../../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ' . BASE_URL . '/reprografia.php');
    exit;
}

try {
    // 2. VERIFICAÇÃO DE SEGURANÇA: Checa se há solicitações pendentes
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM SolicitacaoImpressao WHERE status IN ('Nova', 'Lida')");
    $stmt_check->execute();
    $solicitacoes_pendentes = $stmt_check->fetchColumn();

    if ($solicitacoes_pendentes > 0) {
        // Se houver solicitações pendentes, aborta a operação
        $_SESSION['mensagem_erro'] = "Limpeza abortada. Existem {$solicitacoes_pendentes} solicitações pendentes que precisam ser processadas.";
        header('Location: ' . BASE_URL . '/pages/reprografia/dashboard_reprografia.php');
        exit;
    }

    // 3. LÓGICA DE LIMPEZA DE ARQUIVOS
    $uploadsDir = realpath(__DIR__ . '/../../uploads');
    $removidos = 0;
    $erros = 0;

    if ($uploadsDir && is_dir($uploadsDir)) {
        foreach (glob($uploadsDir . '/*') as $item) {
            if (basename($item) === '.htaccess' || basename($item) === 'index.html') {
                continue;
            }
            if (is_file($item)) {
                if (@unlink($item)) {
                    $removidos++;
                } else {
                    $erros++;
                    error_log("Não foi possível remover o arquivo: " . $item);
                }
            }
        }
    } else {
        throw new Exception('O diretório de uploads não foi encontrado.');
    }

    // 4. DEFINE A MENSAGEM DE FEEDBACK NA SESSÃO
    if ($erros > 0) {
        $_SESSION['mensagem_erro'] = "Limpeza concluída com erros. {$removidos} arquivo(s) removido(s), mas {$erros} falharam.";
    } else {
        $_SESSION['mensagem_sucesso'] = "Limpeza concluída! {$removidos} arquivo(s) foram removidos da pasta de uploads.";
    }

} catch (Exception $e) {
    $_SESSION['mensagem_erro'] = 'Erro durante a limpeza: ' . $e->getMessage();
}

// 5. REDIRECIONA DE VOLTA PARA O PAINEL DA REPROGRAFIA
header('Location: ' . BASE_URL . '/pages/reprografia/dashboard_reprografia.php');
exit;
