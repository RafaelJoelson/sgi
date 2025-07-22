<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// 1. VERIFICAÇÃO DE PERMISSÃO
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

// 2. VALIDAÇÃO DOS INPUTS
$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['Aceita', 'Rejeitada'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados da requisição são inválidos.']);
    exit;
}

// Verifica horário permitido para aceitação de solicitações
date_default_timezone_set('America/Sao_Paulo');
$hora_atual = (int)date('H');
if ($status === 'Aceita' && ($hora_atual < HORARIO_FUNC_INICIO || $hora_atual >= HORARIO_FUNC_FIM)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Solicitações só podem ser aceitas entre 17:00 e 21:00.']);
    exit;
}

$reprografia_id = $_SESSION['usuario']['id'];

try {
    // 3. INICIA A TRANSAÇÃO
    $conn->beginTransaction();

    // 4. ATUALIZA O STATUS DA SOLICITAÇÃO
    $stmt = $conn->prepare(
        "UPDATE SolicitacaoImpressao 
         SET status = :status, reprografia_id = :reprografia_id 
         WHERE id = :id AND status IN ('Nova', 'Lida')"
    );
    $stmt->execute([
        ':status' => $status,
        ':reprografia_id' => $reprografia_id,
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        
        $sol_stmt = $conn->prepare("SELECT * FROM SolicitacaoImpressao WHERE id = ?");
        $sol_stmt->execute([$id]);
        $s = $sol_stmt->fetch(PDO::FETCH_ASSOC);

        if ($s) {
            // 5. LÓGICA DE DECREMENTO DE COTA (APENAS SE 'ACEITA')
            if ($status === 'Aceita') {
                $total = (int)$s['qtd_copias'] * (int)$s['qtd_paginas'];
                if ($s['tipo_solicitante'] === 'Aluno') {
                    $aluno_stmt = $conn->prepare("SELECT matricula, cota_id FROM Aluno WHERE cpf = ?");
                    $aluno_stmt->execute([$s['cpf_solicitante']]);
                    $aluno_data = $aluno_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($aluno_data && $aluno_data['cota_id']) {
                        $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + ? WHERE id = ?")->execute([$total, $aluno_data['cota_id']]);
                        $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas) VALUES (?, ?, ?, ?)")->execute([$id, 'Aluno', $aluno_data['matricula'], $total]);
                    }
                } elseif ($s['tipo_solicitante'] === 'Servidor') {
                    $servidor_stmt = $conn->prepare("SELECT siape FROM Servidor WHERE cpf = ?");
                    $servidor_stmt->execute([$s['cpf_solicitante']]);
                    $siape = $servidor_stmt->fetchColumn();
                    if ($siape) {
                        $coluna_cota = (int)$s['colorida'] === 1 ? 'cota_color_usada' : 'cota_pb_usada';
                        $conn->prepare("UPDATE CotaServidor SET $coluna_cota = $coluna_cota + ? WHERE siape = ?")->execute([$total, $siape]);
                        $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas) VALUES (?, ?, ?, ?)")->execute([$id, 'Servidor', $siape, $total]);
                    }
                }
            }

            // 6. CRIAÇÃO DA NOTIFICAÇÃO PARA O SOLICITANTE
            $nome_arquivo = $s['arquivo_path'] ? basename($s['arquivo_path']) : 'Solicitação no Balcão';
            $mensagem = "Sua solicitação para '{$nome_arquivo}' foi {$status}.";
            
            $stmt_notificacao = $conn->prepare("INSERT INTO Notificacao (solicitacao_id, destinatario_cpf, mensagem) VALUES (:sol_id, :cpf, :msg)");
            $stmt_notificacao->execute([
                ':sol_id' => $id,
                ':cpf' => $s['cpf_solicitante'],
                ':msg' => $mensagem
            ]);
        }
        
        $conn->commit();
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado e notificação enviada com sucesso!']);

    } else {
        $conn->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao atualizar. A solicitação pode já ter sido processada.']);
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro ao atualizar status: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de banco de dados.']);
}
