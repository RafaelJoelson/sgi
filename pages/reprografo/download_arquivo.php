<?php
require_once '../../includes/config.php'; // Ajuste o caminho se necessário
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO BÁSICA
// Garante que um usuário esteja logado.
if (!isset($_SESSION['usuario']['tipo'])) {
    http_response_code(403); // Forbidden
    exit('Acesso negado. Você precisa estar logado.');
}

// 2. VALIDAÇÃO DO INPUT
// Aceita tanto 'id' (usado no painel do reprografo) quanto 'id_solicitacao'
$id_solicitacao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) 
                ?: filter_input(INPUT_GET, 'id_solicitacao', FILTER_VALIDATE_INT);

if (!$id_solicitacao) {
    http_response_code(400); // Bad Request
    exit('ID da solicitação inválido.');
}

try {
    $user_type = $_SESSION['usuario']['tipo'];
    $sql = "";
    $params = [':id_solicitacao' => $id_solicitacao];

    // 3. LÓGICA DE PERMISSÃO DINÂMICA
    if ($user_type === 'reprografo') {
        // Reprógrafos podem baixar o arquivo de qualquer solicitação, verificando apenas o ID.
        $sql = "SELECT arquivo_path FROM SolicitacaoImpressao WHERE id = :id_solicitacao";
    } 
    elseif (($user_type === 'aluno' || $user_type === 'servidor') && isset($_SESSION['usuario']['cpf'])) {
        // Alunos e Servidores só podem baixar os próprios arquivos.
        $sql = "SELECT arquivo_path FROM SolicitacaoImpressao WHERE id = :id_solicitacao AND cpf_solicitante = :cpf_usuario";
        $params[':cpf_usuario'] = $_SESSION['usuario']['cpf'];
    } 
    else {
        // Se o tipo de usuário for desconhecido ou não tiver CPF (quando necessário)
        http_response_code(403);
        exit('Permissão de download negada para este tipo de usuário.');
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se a consulta não retornar nada, o pedido não existe ou o usuário não tem permissão.
    if (!$solicitacao || empty($solicitacao['arquivo_path'])) {
        http_response_code(404); // Not Found
        exit('Arquivo não encontrado ou acesso negado.');
    }

    $nome_arquivo = $solicitacao['arquivo_path'];
    $caminho_completo = realpath(__DIR__ . '/../../uploads') . '/' . $nome_arquivo;

    // 4. VERIFICAÇÃO DA EXISTÊNCIA FÍSICA DO ARQUIVO
    if (!file_exists($caminho_completo)) {
        http_response_code(404);
        exit('O arquivo não existe mais no servidor (pode ter sido removido pela rotina de limpeza).');
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
    error_log("Erro no download: " . $e->getMessage());
    http_response_code(500);
    exit('Ocorreu um erro interno. Tente novamente mais tarde.');
}
