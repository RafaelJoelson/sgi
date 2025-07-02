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

// Verifica horário permitido para aceitação de solicitações (até 21:00)
$hora_atual = (int)date('H');
if ($status === 'Aceita' && ($hora_atual < 0 || $hora_atual >= 21)) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Solicitações só podem ser aceitas entre 07:00 e 21:00.']);
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
            // Sempre usar o valor salvo na solicitação
            $num_paginas = (int)$s['qtd_paginas'];
            $total = $num_paginas * $qtd_copias;
            if ($tipo === 'Aluno') {
                // Decrementa cota do aluno (PB)
                $aluno = $conn->prepare("SELECT cota_id FROM Aluno WHERE cpf = ?");
                $aluno->execute([$referencia]);
                $cota_id = $aluno->fetchColumn();
                if ($cota_id) {
                    $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + ? WHERE id = ?")->execute([$total, $cota_id]);
                    // Log de decremento
                    $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas) VALUES (?, ?, ?, ?)")
                        ->execute([$id, $tipo, $cota_id, $total]);
                }
            } else if ($tipo === 'Servidor') {
                // Buscar SIAP do servidor pelo CPF
                $servidor = $conn->prepare("SELECT siap FROM Servidor WHERE cpf = ?");
                $servidor->execute([$referencia]);
                $siap = $servidor->fetchColumn();
                if ($siap) {
                    if ($colorida) {
                        $conn->prepare("UPDATE CotaServidor SET cota_color_usada = cota_color_usada + ? WHERE siap = ?")->execute([$total, $siap]);
                    } else {
                        $conn->prepare("UPDATE CotaServidor SET cota_pb_usada = cota_pb_usada + ? WHERE siap = ?")->execute([$total, $siap]);
                    }
                    // Log de decremento
                    $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas) VALUES (?, ?, ?, ?)")
                        ->execute([$id, $tipo, $siap, $total]);
                }
            }
        }
    } else if ($status === 'Rejeitada') {
        // Não altera cota ao rejeitar
        // (removido código de devolução de cota)
    }
    echo json_encode(['sucesso'=>true,'mensagem'=>'Status atualizado com sucesso!']);
} else {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Falha ao atualizar status.']);
}
