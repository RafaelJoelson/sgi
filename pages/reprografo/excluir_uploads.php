<?php
// Painel seguro para listagem e exclusão de arquivos da pasta uploads
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}
$uploadsDir = realpath(__DIR__ . '/../../uploads');
if (!$uploadsDir) {
    http_response_code(500);
    echo json_encode(['erro' => 'Diretório de uploads não encontrado.']);
    exit;
}
header('Content-Type: application/json');
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
if ($acao === 'listar') {
    $arquivos = array_values(array_filter(scandir($uploadsDir), function($f) use ($uploadsDir) {
        return is_file($uploadsDir . DIRECTORY_SEPARATOR . $f) && !in_array($f, ['.','..']);
    }));
    echo json_encode(['arquivos' => $arquivos]);
    exit;
}
if ($acao === 'excluir') {
    $arquivos = $_POST['arquivos'] ?? [];
    if (!is_array($arquivos) || empty($arquivos)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum arquivo selecionado.']);
        exit;
    }
    $excluidos = [];
    $falhas = [];
    foreach ($arquivos as $arq) {
        $arq = basename($arq); // previne path traversal
        $caminho = $uploadsDir . DIRECTORY_SEPARATOR . $arq;
        if (is_file($caminho) && strpos(realpath($caminho), $uploadsDir) === 0) {
            if (@unlink($caminho)) $excluidos[] = $arq;
            else $falhas[] = $arq;
        } else {
            $falhas[] = $arq;
        }
    }
    echo json_encode([
        'sucesso' => count($falhas) === 0,
        'excluidos' => $excluidos,
        'falhas' => $falhas,
        'mensagem' => count($falhas) === 0 ? 'Arquivos excluídos com sucesso.' : 'Alguns arquivos não puderam ser excluídos.'
    ]);
    exit;
}
echo json_encode(['erro' => 'Ação inválida.']);
