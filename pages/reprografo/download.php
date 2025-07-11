<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO: Apenas um reprografo logado pode usar este script.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    http_response_code(403); // Forbidden
    exit('Acesso negado. Apenas reprografos podem baixar arquivos.');
}

// 2. VALIDAÇÃO DO INPUT
$id_solicitacao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_solicitacao) {
    http_response_code(400); // Bad Request
    exit('ID da solicitação inválido.');
}

try {
    // 3. CONSULTA AO BANCO DE DADOS (LÓGICA CORRIGIDA)
    // Busca o arquivo pelo ID da solicitação, sem verificar o dono,
    // pois o reprografo precisa ter acesso a todos.
    $stmt = $conn->prepare("SELECT arquivo_path FROM SolicitacaoImpressao WHERE id = :id_solicitacao");
    $stmt->execute([':id_solicitacao' => $id_solicitacao]);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se a consulta não retornar nada ou não houver arquivo, o pedido não existe ou é de balcão.
    if (!$solicitacao || empty($solicitacao['arquivo_path'])) {
        http_response_code(404); // Not Found
        exit('Arquivo não encontrado para esta solicitação.');
    }

    $nome_arquivo = $solicitacao['arquivo_path'];
    // Constrói o caminho completo e seguro para o arquivo.
    $caminho_completo = realpath(__DIR__ . '/../../uploads') . '/' . $nome_arquivo;

    // 4. VERIFICAÇÃO DA EXISTÊNCIA FÍSICA DO ARQUIVO
    if (!file_exists($caminho_completo)) {
        http_response_code(404);
        exit('O arquivo não existe mais no servidor. Pode ter sido removido pela rotina de limpeza.');
    }

    // 5. FORÇAR O DOWNLOAD
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($caminho_completo) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($caminho_completo));
    
    flush(); 
    readfile($caminho_completo);
    exit;

} catch (PDOException $e) {
    error_log("Erro no download pelo reprografo: " . $e->getMessage());
    http_response_code(500);
    exit('Ocorreu um erro interno ao processar sua solicitação.');
}
