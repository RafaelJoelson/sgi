<?php
// Script para limpar arquivos antigos da pasta uploads (mais de 15 dias)
$uploadsDir = __DIR__ . '/uploads';
$limiteDias = 15;
$agora = time();

if (!is_dir($uploadsDir)) {
    echo "Pasta de uploads não encontrada: $uploadsDir\n";
    exit(1);
}

$removidos = 0;
foreach (glob($uploadsDir . '/*') as $arquivo) {
    if (is_file($arquivo)) {
        $modificado = filemtime($arquivo);
        if ($agora - $modificado > ($limiteDias * 24 * 60 * 60)) {
            if (unlink($arquivo)) {
                $removidos++;
            }
        }
    }
}
echo "Arquivos removidos: $removidos\n";
?>
