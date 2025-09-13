<?php
require_once '../../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    http_response_code(403);
    exit('Acesso negado.');
}

$id_solicitacao = filter_input(INPUT_GET, 'id_solicitacao', FILTER_VALIDATE_INT);
if (!$id_solicitacao) {
    http_response_code(400);
    exit('ID da solicitação inválido.');
}

try {
    $stmt = $conn->prepare(
        "SELECT arquivo_path FROM SolicitacaoImpressao 
         WHERE id = :id_solicitacao AND usuario_id = :usuario_id"
    );
    $stmt->execute([
        ':id_solicitacao' => $id_solicitacao,
        ':usuario_id' => $_SESSION['usuario']['id']
    ]);

    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitacao || empty($solicitacao['arquivo_path'])) {
        http_response_code(404);
        exit('Arquivo não encontrado ou acesso negado.');
    }

    $nome_arquivo = $solicitacao['arquivo_path'];
    $caminho_completo = realpath(__DIR__ . '/../../uploads') . '/' . $nome_arquivo;

    if (!file_exists($caminho_completo)) {
        http_response_code(404);
        exit('O arquivo não existe mais no servidor.');
    }

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
    error_log("Erro no download (servidor): " . $e->getMessage());
    http_response_code(500);
    exit('Ocorreu um erro interno. Tente novamente mais tarde.');
}