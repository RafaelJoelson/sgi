<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é reprografo logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['Aceita', 'Rejeitada'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

// Verifica horário permitido para aceitação de solicitações (entre 7:00 e 20:59)
date_default_timezone_set('America/Sao_Paulo');
$hora_atual = (int)date('H');
if ($status === 'Aceita' && ($hora_atual < 7 || $hora_atual >= 21)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Solicitações só podem ser aceitas entre 07:00 e 21:00.']);
    exit;
}

// Pega o ID do reprografo logado da sessão
$reprografo_id = $_SESSION['usuario']['id'];

try {
    $conn->beginTransaction();

    // MUDANÇA: Atualiza a coluna correta 'reprografo_id' com o ID do reprografo
    $stmt = $conn->prepare("UPDATE SolicitacaoImpressao SET status = :status, reprografo_id = :reprografo_id WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':reprografo_id' => $reprografo_id,
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        // Se aceitou, processa decremento de cota
        if ($status === 'Aceita') {
            // Busca dados da solicitação
            $sol = $conn->prepare("SELECT * FROM SolicitacaoImpressao WHERE id = ?");
            $sol->execute([$id]);
            $s = $sol->fetch(PDO::FETCH_ASSOC);
            
            if ($s) {
                $qtd_copias = (int)$s['qtd_copias'];
                $num_paginas = (int)$s['qtd_paginas'];
                $total = $num_paginas * $qtd_copias;
                $tipo = $s['tipo_solicitante'];
                $referencia_cpf = $s['cpf_solicitante'];

                if ($tipo === 'Aluno') {
                    // Decrementa cota do aluno
                    $aluno = $conn->prepare("SELECT matricula, cota_id FROM Aluno WHERE cpf = ?");
                    $aluno->execute([$referencia_cpf]);
                    $aluno_data = $aluno->fetch(PDO::FETCH_ASSOC);
                    
                    if ($aluno_data && $aluno_data['cota_id']) {
                        $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + ? WHERE id = ?")->execute([$total, $aluno_data['cota_id']]);
                        // Log de decremento usando a MATRÍCULA como referência
                        $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas) VALUES (?, ?, ?, ?)")
                             ->execute([$id, $tipo, $aluno_data['matricula'], $total]);
                    }
                } else if ($tipo === 'Servidor') {
                    // Decrementa cota do servidor
                    $servidor = $conn->prepare("SELECT siape FROM Servidor WHERE cpf = ?");
                    $servidor->execute([$referencia_cpf]);
                    $siape = $servidor->fetchColumn();
                    
                    if ($siape) {
                        $coluna_cota = (int)$s['colorida'] === 1 ? 'cota_color_usada' : 'cota_pb_usada';
                        $conn->prepare("UPDATE CotaServidor SET $coluna_cota = $coluna_cota + ? WHERE siape = ?")->execute([$total, $siape]);
                        // Log de decremento usando o SIAPE como referência
                        $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas) VALUES (?, ?, ?, ?)")
                             ->execute([$id, $tipo, $siape, $total]);
                    }
                }
            }
        }
        
        $conn->commit();
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado com sucesso!']);

    } else {
        $conn->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao atualizar status. A solicitação pode já ter sido processada.']);
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro ao atualizar status: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de banco de dados.']);
}
