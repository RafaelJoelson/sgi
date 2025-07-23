<?php
session_start();
require_once '../../../includes/config.php';
header('Content-Type: application/json');

// --- 1. Verificação de Permissão ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

// --- 2. Validação de Inputs ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método de requisição inválido.']);
    exit;
}

$cpf_aluno = $_SESSION['usuario']['cpf'];
$id_aluno = $_SESSION['usuario']['id']; // Assumindo que o 'id' (matrícula) está na sessão

$is_balcao = isset($_POST['solicitar_balcao']);
$qtd_copias = filter_input(INPUT_POST, 'qtd_copias', FILTER_VALIDATE_INT);
$qtd_paginas = filter_input(INPUT_POST, 'qtd_paginas', FILTER_VALIDATE_INT);

// Validação mais rigorosa
if (!$qtd_copias || !$qtd_paginas || $qtd_copias < 1 || $qtd_paginas < 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Quantidades de cópias e páginas devem ser números válidos e maiores que zero.']);
    exit;
}

// Se não for de balcão, o arquivo é obrigatório
if (!$is_balcao && (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O envio de um arquivo é obrigatório e parece ter falhado.']);
    exit;
}

try {
    // --- 3. Iniciar Transação ---
    $conn->beginTransaction();

    // Verifica cota do aluno
    $stmt_cota = $conn->prepare(
        'SELECT c.cota_total, c.cota_usada 
         FROM Aluno a 
         JOIN CotaAluno c ON a.cota_id = c.id 
         WHERE a.cpf = ?'
    );
    $stmt_cota->execute([$cpf_aluno]);
    $cota = $stmt_cota->fetch(PDO::FETCH_ASSOC);

    if (!$cota) {
        throw new Exception('Cota de impressão não encontrada para este aluno.');
    }

    $total_paginas_solicitadas = $qtd_copias * $qtd_paginas;
    $cota_disponivel = $cota['cota_total'] - $cota['cota_usada'];

    if ($total_paginas_solicitadas > $cota_disponivel) {
        throw new Exception('Cota de impressão insuficiente. Disponível: ' . $cota_disponivel . ' páginas.');
    }

    // --- 4. Lógica Condicional de Upload ---
    $nome_arquivo_final = null; // Padrão para solicitação de balcão

    if (!$is_balcao) {
        $arquivo = $_FILES['arquivo'];
        
        // Validações do arquivo
        $permitidos = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $permitidos)) {
            throw new Exception('Tipo de arquivo inválido. Permitidos: ' . implode(', ', $permitidos));
        }
        if ($arquivo['size'] > 10 * 1024 * 1024) { // Limite de 10MB
            throw new Exception('Arquivo muito grande. O limite é de 10MB.');
        }

        // Gera nome de arquivo seguro e único
        $dir_uploads = '../../uploads/';
        if (!is_dir($dir_uploads)) mkdir($dir_uploads, 0775, true);
        
        $nome_arquivo_final = 'aluno_' . $id_aluno . '_' . uniqid() . '.' . $extensao;
        $caminho_destino = $dir_uploads . $nome_arquivo_final;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
            throw new Exception('Falha crítica ao salvar o arquivo no servidor.');
        }
    }

    // --- 5. Inserir Solicitação no Banco ---
    $stmt_insert = $conn->prepare(
        "INSERT INTO SolicitacaoImpressao (cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, qtd_paginas, colorida, status) 
         VALUES (?, ?, ?, ?, ?, 0, 'Nova')" // colorida é sempre 0 para aluno
    );
    $stmt_insert->execute([
        $cpf_aluno,
        'Aluno',
        $nome_arquivo_final, // Será NULL se for de balcão
        $qtd_copias,
        $qtd_paginas
    ]);
    $solicitacao_id = $conn->lastInsertId(); // Pega o ID da solicitação recém-criada

    // --- 6. MUDANÇA: CRIAÇÃO DA NOTIFICAÇÃO PARA O ALUNO ---
    $nome_para_msg = $nome_arquivo_final ? basename($nome_arquivo_final) : 'Solicitação no Balcão';
    $mensagem_notificacao = "Sua solicitação para '{$nome_para_msg}' foi recebida e está aguardando análise.";
    
    $stmt_notificacao = $conn->prepare("INSERT INTO Notificacao (solicitacao_id, destinatario_cpf, mensagem) VALUES (:sol_id, :cpf, :msg)");
    $stmt_notificacao->execute([
        ':sol_id' => $solicitacao_id,
        ':cpf' => $cpf_aluno,
        ':msg' => $mensagem_notificacao
    ]);
    // --- FIM DA MUDANÇA ---

    // --- 7. Confirmar Transação ---
    $conn->commit();

    echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada com sucesso!']);

} catch (Exception $e) {
    // --- 8. Reverter Transação em Caso de Erro ---
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
