<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é reprografo logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode([]);
    exit;
}

try {
    // Busca solicitações pendentes (status Nova ou Lida)
    // A consulta une as tabelas Aluno e Servidor para obter o nome do solicitante.
    $sql = "SELECT 
                s.id, 
                s.arquivo_path as arquivo, 
                s.qtd_copias, 
                s.qtd_paginas, 
                s.colorida, 
                s.status, 
                DATE_FORMAT(s.data_criacao, '%d/%m/%Y %H:%i') as data, 
                s.tipo_solicitante,
                CASE 
                    WHEN s.tipo_solicitante = 'Aluno' THEN CONCAT(a.nome, ' ', a.sobrenome)
                    WHEN s.tipo_solicitante = 'Servidor' THEN CONCAT(v.nome, ' ', v.sobrenome)
                    ELSE 'Desconhecido'
                END as nome_solicitante
            FROM SolicitacaoImpressao s
            LEFT JOIN Aluno a ON s.tipo_solicitante = 'Aluno' AND s.cpf_solicitante = a.cpf
            LEFT JOIN Servidor v ON s.tipo_solicitante = 'Servidor' AND s.cpf_solicitante = v.cpf
            WHERE s.status IN ('Nova', 'Lida')
            ORDER BY s.data_criacao DESC -- CORREÇÃO: Alterado de ASC para DESC
            LIMIT 50"; // Limite para evitar sobrecarga na tela

    $stmt = $conn->query($sql);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($solicitacoes);

} catch (Exception $e) {
    error_log("Erro ao listar solicitações pendentes para reprografo: " . $e->getMessage());
    echo json_encode([]);
}
