<?php
require_once '../../../includes/config.php'; // Ajuste o caminho se necessário
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO BÁSICA
// Garante que apenas um usuário logado possa tentar baixar algo.
if (!isset($_SESSION['usuario']['cpf'])) {
    http_response_code(403); // Forbidden
    exit('Acesso negado. Você precisa estar logado.');
}

// 2. VALIDAÇÃO DO INPUT
// Garante que o ID da solicitação foi fornecido e é um número.
$id_solicitacao = filter_input(INPUT_GET, 'id_solicitacao', FILTER_VALIDATE_INT);
if (!$id_solicitacao) {
    http_response_code(400); // Bad Request
    exit('ID da solicitação inválido.');
}

try {
    // 3. CONSULTA SEGURA AO BANCO DE DADOS
    // Busca o arquivo, mas APENAS SE o ID da solicitação pertencer ao CPF do usuário logado.
    $stmt = $conn->prepare(
        "SELECT arquivo_path FROM SolicitacaoImpressao 
         WHERE id = :id_solicitacao AND cpf_solicitante = :cpf_usuario"
    );
    $stmt->execute([
        ':id_solicitacao' => $id_solicitacao,
        ':cpf_usuario' => $_SESSION['usuario']['cpf']
    ]);

    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se a consulta não retornar nada, ou o pedido não existe ou não pertence ao usuário.
    if (!$solicitacao || empty($solicitacao['arquivo_path'])) {
        http_response_code(404); // Not Found
        exit('Arquivo não encontrado ou acesso negado.');
    }

    $nome_arquivo = $solicitacao['arquivo_path'];
    // Constrói o caminho completo e seguro para o arquivo.
    // __DIR__ pega o diretório atual do script (ex: /pages/aluno/)
    // O caminho final será algo como /uploads/nome_do_arquivo.pdf
    $caminho_completo = realpath(__DIR__ . '/../../uploads') . '/' . $nome_arquivo;

    // 4. VERIFICAÇÃO DA EXISTÊNCIA FÍSICA DO ARQUIVO
    if (!file_exists($caminho_completo)) {
        http_response_code(404);
        exit('O arquivo não existe mais no servidor.');
    }

    // 5. FORÇAR O DOWNLOAD
    // Define os cabeçalhos para instruir o navegador a baixar o arquivo.
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // Tipo genérico que força o download
    header('Content-Disposition: attachment; filename="' . basename($caminho_completo) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($caminho_completo));
    
    // Limpa o buffer de saída para evitar corrupção do arquivo
    flush(); 
    
    // Lê o arquivo e o envia para o navegador
    readfile($caminho_completo);
    exit; // Termina o script

} catch (PDOException $e) {
    // Em caso de erro de banco de dados
    error_log("Erro no download: " . $e->getMessage()); // Log para o admin
    http_response_code(500); // Internal Server Error
    exit('Ocorreu um erro interno. Tente novamente mais tarde.');
}