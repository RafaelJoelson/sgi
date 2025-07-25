<?php
// Histórico completo de solicitações do servidor
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';

$cpf_servidor = $_SESSION['usuario']['cpf'];

// A consulta PHP já está correta, buscando o 'id' para o link de download.
$stmt = $conn->prepare(
    'SELECT id, arquivo_path, qtd_copias, qtd_paginas, colorida, status, data_criacao 
     FROM SolicitacaoImpressao 
     WHERE cpf_solicitante = ? AND tipo_solicitante = "Servidor" 
     ORDER BY data_criacao DESC'
);
$stmt->execute([$cpf_servidor]);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Vinculando a folha de estilos do servidor (que deve ser similar à do aluno) -->
<link rel="stylesheet" href="./css/dashboard_servidor.css?v=<?= ASSET_VERSION ?>">
<link rel="stylesheet" href="./css/historico_solicitacoes.css?v=<?= ASSET_VERSION ?>">

<main class="container">
    <div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
    <h3>Histórico de Solicitações</h3>
    <div class="tabela-container">
        <div id="tabela-solicitacoes-historico">
            <table>
                <thead>
                    <tr>
                        <th>Arquivo / Tipo</th>
                        <th>Cópias</th>
                        <th>Páginas</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitacoes)): ?>
                        <tr><td colspan="6" style="text-align: center;">Nenhuma solicitação encontrada no seu histórico.</td></tr>
                    <?php else: foreach ($solicitacoes as $s): ?>
                        <tr>
                            <td>
                                <?php
                                // A lógica para exibir o tipo de solicitação e o link seguro permanece
                                if (empty($s['arquivo_path'])) {
                                    echo '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                                } else {
                                    echo '<a href="./functions/download.php?id_solicitacao=' . htmlspecialchars($s['id']) . '" target="_blank" title="Baixar ' . htmlspecialchars($s['arquivo_path']) . '">';
                                    echo '<i class="fas fa-download"></i> ' . htmlspecialchars($s['arquivo_path']);
                                    echo '</a>';
                                }
                                ?>
                            </td>
                            <td><?= (int)$s['qtd_copias'] ?></td>
                            <td><?= (int)$s['qtd_paginas'] ?></td>
                            <td><?= $s['colorida'] ? 'Colorida' : 'P&B' ?></td>
                            <td><?= htmlspecialchars($s['status']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['data_criacao']))) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- O botão "Voltar" agora usa a tag <button> com onclick para receber o estilo correto do CSS -->
    <button onclick="window.location.href='dashboard_servidor.php'">&larr; Voltar ao Painel</button>
</main>
<?php require_once '../../includes/footer.php'; ?>
