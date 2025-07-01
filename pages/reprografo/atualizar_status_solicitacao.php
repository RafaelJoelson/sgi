<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é reprografo logado (ajuste conforme sua lógica de sessão)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['Aceita','Rejeitada'])) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Dados inválidos.']);
    exit;
}

$stmt = $conn->prepare("UPDATE SolicitacaoImpressao SET status = :status, cpf_reprografo = :cpf WHERE id = :id");
$stmt->execute([
    ':status' => $status,
    ':cpf' => $_SESSION['usuario']['cpf'],
    ':id' => $id
]);

if ($stmt->rowCount()) {
    // Se aceitou, processa decremento de cota
    if ($status === 'Aceita') {
        // Busca dados da solicitação
        $sol = $conn->prepare("SELECT * FROM SolicitacaoImpressao WHERE id = ?");
        $sol->execute([$id]);
        $s = $sol->fetch(PDO::FETCH_ASSOC);
        if ($s) {
            $arquivo_path = '../../uploads/' . $s['arquivo_path'];
            $qtd_copias = (int)$s['qtd_copias'];
            $colorida = (int)$s['colorida'];
            $tipo = $s['tipo_solicitante'];
            $referencia = $s['cpf_solicitante'];
            // Conta páginas se for PDF
            $num_paginas = 1;
            $ext = strtolower(pathinfo($arquivo_path, PATHINFO_EXTENSION));
            if ($ext === 'pdf' && file_exists($arquivo_path)) {
                $pdf = file_get_contents($arquivo_path);
                preg_match_all("/\/Type\s*\/Page[^s]/", $pdf, $matches);
                $num_paginas = max(1, count($matches[0]));
            }
            $total = $num_paginas * $qtd_copias;
            if ($tipo === 'Aluno') {
                // Decrementa cota do aluno (PB)
                $aluno = $conn->prepare("SELECT cota_id FROM Aluno WHERE cpf = ?");
                $aluno->execute([$referencia]);
                $cota_id = $aluno->fetchColumn();
                if ($cota_id) {
                    $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + ? WHERE id = ?")->execute([$total, $cota_id]);
                }
            } else if ($tipo === 'Servidor') {
                // Decrementa cota do servidor (PB ou colorida)
                if ($colorida) {
                    $conn->prepare("UPDATE CotaServidor SET cota_color_usada = cota_color_usada + ? WHERE siap = ?")->execute([$total, $referencia]);
                } else {
                    $conn->prepare("UPDATE CotaServidor SET cota_pb_usada = cota_pb_usada + ? WHERE siap = ?")->execute([$total, $referencia]);
                }
            }
            // Log de decremento
            $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_copias) VALUES (?, ?, ?, ?)")
                ->execute([$id, $tipo, $referencia, $total]);
        }
    }
    echo json_encode(['sucesso'=>true,'mensagem'=>'Status atualizado com sucesso!']);
} else {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Falha ao atualizar status.']);
}
