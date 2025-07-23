<?php
require_once '../../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}

$uploadsDir = '../../../uploads';

if (!is_dir($uploadsDir)) {
    $_SESSION['mensagem_erro'] = "Pasta de uploads não encontrada: $uploadsDir";
    header('Location: ../dashboard_reprografia.php');
    exit;
}

$removidos = 0;
foreach (glob($uploadsDir . '/*') as $arquivo) {
    if (is_file($arquivo)) {
        if (@unlink($arquivo)) {
            $removidos++;
        }
    } elseif (is_dir($arquivo)) {
        // Remove diretórios e seus conteúdos
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($arquivo, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                @unlink($file->getRealPath());
            } elseif ($file->isDir()) {
                @rmdir($file->getRealPath());
            }
        }
        @rmdir($arquivo);
        $removidos++;
    }
}
$_SESSION['mensagem_sucesso'] = "Arquivos removidos: $removidos";
header('Location: dashboard_reprografia.php');
exit;
?>
