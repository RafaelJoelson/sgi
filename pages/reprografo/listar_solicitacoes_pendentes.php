<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é reprografo logado (ajuste conforme sua lógica de sessão)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode([]);
    exit;
}

// Busca solicitações pendentes (status Nova ou Lida)
$sql = "SELECT s.id, s.arquivo_path as arquivo, s.qtd_copias, s.colorida, s.status, s.data_criacao as data, s.tipo_solicitante,
        CASE WHEN s.tipo_solicitante = 'Aluno' THEN a.nome ELSE v.nome END as nome_solicitante
        FROM SolicitacaoImpressao s
        LEFT JOIN Aluno a ON s.tipo_solicitante = 'Aluno' AND s.cpf_solicitante = a.cpf
        LEFT JOIN Servidor v ON s.tipo_solicitante = 'Servidor' AND s.cpf_solicitante = v.cpf
        WHERE s.status IN ('Nova', 'Lida')
        ORDER BY s.data_criacao DESC LIMIT 20";
$stmt = $conn->query($sql);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Formata datas
foreach ($solicitacoes as &$s) {
    $s['data'] = date('d/m/Y H:i', strtotime($s['data']));
}
echo json_encode($solicitacoes);
