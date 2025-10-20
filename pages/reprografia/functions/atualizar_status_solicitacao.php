<?php
session_start();
require_once '../../../includes/config.php';
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
    echo json_encode(['sucesso' => false, 'mensagem' => 'Solicitações só podem ser aceitas entre ' . HORARIO_FUNC_INICIO . 'h e ' . HORARIO_FUNC_FIM . 'h.']);
    exit;
}

$reprografia_id = $_SESSION['usuario']['id'];

try {
    $conn->beginTransaction();

    // 3. BUSCA OS DADOS ATUAIS DA SOLICITAÇÃO
    $sol_stmt = $conn->prepare("SELECT si.*, u.cpf AS cpf_solicitante, u.tipo_usuario AS tipo_solicitante FROM SolicitacaoImpressao si JOIN Usuario u ON si.usuario_id = u.id WHERE si.id = ?");
    $sol_stmt->execute([$id]);
    $s = $sol_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$s) {
        throw new Exception("Solicitação não encontrada.");
    }

    // 4. RE-VERIFICAÇÃO DE COTA (A MUDANÇA CRÍTICA)
    if ($status === 'Aceita') {
        $total_paginas = (int)$s['qtd_copias'] * (int)$s['qtd_paginas'];
        
        if ($s['tipo_solicitante'] === 'Aluno') {
            $stmt_cota = $conn->prepare('SELECT c.cota_total, c.cota_usada FROM Aluno a JOIN CotaAluno c ON a.cota_id = c.id JOIN Usuario u ON a.usuario_id = u.id WHERE u.cpf = ?');
            $stmt_cota->execute([$s['cpf_solicitante']]);
            $cota = $stmt_cota->fetch(PDO::FETCH_ASSOC);
            if (!$cota) throw new Exception("Cota do aluno não encontrada.");
            
            $cota_disponivel = $cota['cota_total'] - $cota['cota_usada'];
            if ($total_paginas > $cota_disponivel) {
                throw new Exception("Cota do aluno insuficiente. Disponível: {$cota_disponivel}, Necessário: {$total_paginas}.");
            }
        } elseif ($s['tipo_solicitante'] === 'Servidor') {
            $stmt_cota = $conn->prepare('SELECT cs.* FROM Servidor s JOIN CotaServidor cs ON s.usuario_id = cs.usuario_id JOIN Usuario u ON s.usuario_id = u.id WHERE u.cpf = ?');
            $stmt_cota->execute([$s['cpf_solicitante']]);
            $cota = $stmt_cota->fetch(PDO::FETCH_ASSOC);
            if (!$cota) throw new Exception("Cota do servidor não encontrada.");

            if ((int)$s['colorida'] === 1) {
                $cota_disponivel = $cota['cota_color_total'] - $cota['cota_color_usada'];
                if ($total_paginas > $cota_disponivel) {
                    throw new Exception("Cota colorida do servidor insuficiente. Disponível: {$cota_disponivel}, Necessário: {$total_paginas}.");
                }
            } else {
                $cota_disponivel = $cota['cota_pb_total'] - $cota['cota_pb_usada'];
                if ($total_paginas > $cota_disponivel) {
                    throw new Exception("Cota P&B do servidor insuficiente. Disponível: {$cota_disponivel}, Necessário: {$total_paginas}.");
                }
            }
        }
    }

    // 5. ATUALIZA O STATUS DA SOLICITAÇÃO
    $stmt_update = $conn->prepare("UPDATE SolicitacaoImpressao SET status = :status, reprografia_id = :reprografia_id WHERE id = :id AND status IN ('Nova', 'Lida')");
    $stmt_update->execute([':status' => $status, ':reprografia_id' => $reprografia_id, ':id' => $id]);

    if ($stmt_update->rowCount() > 0) {
        if ($status === 'Aceita') {
            $total_paginas = (int)$s['qtd_copias'] * (int)$s['qtd_paginas'];
            if ($s['tipo_solicitante'] === 'Aluno') {
                $aluno_stmt = $conn->prepare("SELECT a.matricula, a.cota_id FROM Aluno a JOIN Usuario u ON a.usuario_id = u.id WHERE u.cpf = ?");
                $aluno_stmt->execute([$s['cpf_solicitante']]);
                $aluno_data = $aluno_stmt->fetch(PDO::FETCH_ASSOC);
                if ($aluno_data && $aluno_data['cota_id']) {
                    $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + ? WHERE id = ?")->execute([$total_paginas, $aluno_data['cota_id']]);
                    $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, usuario_id, qtd_cotas) VALUES (?, ?, ?)")->execute([$id, $s['usuario_id'], $total_paginas]);
                }
            } elseif ($s['tipo_solicitante'] === 'Servidor') {
                $servidor_stmt = $conn->prepare("SELECT s.siape FROM Servidor s JOIN Usuario u ON s.usuario_id = u.id WHERE u.cpf = ?");
                $servidor_stmt->execute([$s['cpf_solicitante']]);
                $siape = $servidor_stmt->fetchColumn();
                if ($siape) {
                    $coluna_cota = (int)$s['colorida'] === 1 ? 'cota_color_usada' : 'cota_pb_usada';
                    $conn->prepare("UPDATE CotaServidor SET $coluna_cota = $coluna_cota + ? WHERE usuario_id = ?")->execute([$total_paginas, $s['usuario_id']]);
                    $conn->prepare("INSERT INTO LogDecrementoCota (solicitacao_id, usuario_id, qtd_cotas) VALUES (?, ?, ?)")->execute([$id, $s['usuario_id'], $total_paginas]);
                }
            }
        }

        // 6. CRIAÇÃO DA NOTIFICAÇÃO PARA O SOLICITANTE
        $nome_arquivo = $s['arquivo_path'] ? basename($s['arquivo_path']) : 'Solicitação no Balcão';
        $mensagem = "Sua solicitação para '{$nome_arquivo}' foi {$status}.";
        
        $stmt_notificacao = $conn->prepare("INSERT INTO Notificacao (solicitacao_id, destinatario_id, mensagem) VALUES (:sol_id, :dest_id, :msg)");
        $stmt_notificacao->execute([':sol_id' => $id, ':dest_id' => $s['usuario_id'], ':msg' => $mensagem]);
        
        $conn->commit();
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado e notificação enviada com sucesso!']);

    } else {
        $conn->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao atualizar. A solicitação pode já ter sido processada.']);
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro ao atualizar status: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
