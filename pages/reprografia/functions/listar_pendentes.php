<?php
/**
 * Lista todas as solicitações com status 'Nova' ou 'Lida' para o painel da reprografia.
 */
require_once '../../../includes/config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

try {
    // Query complexa que une todas as informações necessárias sobre o solicitante
    $sql = "SELECT 
                si.id, si.arquivo_path, si.qtd_copias, si.qtd_paginas, si.colorida, si.status, 
                DATE_FORMAT(si.data_criacao, '%d/%m/%Y %H:%i') as data_formatada,
                u.nome, u.sobrenome, u.tipo_usuario,
                a.matricula,
                s.siape,
                c.sigla AS curso_sigla,
                t.periodo AS turma_periodo
            FROM SolicitacaoImpressao si
            JOIN Usuario u ON si.usuario_id = u.id
            LEFT JOIN Aluno a ON u.id = a.usuario_id AND u.tipo_usuario = 'aluno'
            LEFT JOIN Servidor s ON u.id = s.usuario_id AND u.tipo_usuario = 'servidor'
            LEFT JOIN CotaAluno ca ON a.cota_id = ca.id
            LEFT JOIN Turma t ON ca.turma_id = t.id
            LEFT JOIN Curso c ON t.curso_id = c.id
            WHERE si.status IN ('Nova', 'Lida')
            ORDER BY si.data_criacao DESC";

    $stmt = $conn->query($sql);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marca as solicitações 'Novas' como 'Lidas'
    $stmt_update = $conn->prepare("UPDATE SolicitacaoImpressao SET status = 'Lida' WHERE status = 'Nova'");
    $stmt_update->execute();

    echo json_encode(['sucesso' => true, 'solicitacoes' => $solicitacoes]);

} catch (PDOException $e) {
    error_log("Erro ao listar solicitações pendentes: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar solicitações.']);
}
?>