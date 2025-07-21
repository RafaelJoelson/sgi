<?php
// Dashboard do Servidor
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_servidor.css">
<main class="container">
    <!-- Container para as notificações "toast" -->
    <div id="toast-notification-container"></div>
        <button id="btn-ativar-notificacoes" class="btn-notificacao" title="Clique para permitir notificações no navegador">
            <i class="fas fa-bell"></i> Ativar Notificações
        </button>
    <h4>Servidor(a): <?= htmlspecialchars($_SESSION['usuario']['nome'] . (isset($_SESSION['usuario']['sobrenome']) ? ' ' . $_SESSION['usuario']['sobrenome'] : '')) ?></h4>
    <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
    <?php
        // Exibe botão de acesso ao painel administrativo conforme o tipo/setor do servidor
        $tipoServidor = $_SESSION['usuario']['tipo_servidor'] ?? '';
        $setorAdmin = $_SESSION['usuario']['setor_admin'] ?? '';
        if ($tipoServidor === 'CAD' || $setorAdmin === 'CAD') {
            echo '<a class="btn-menu" href="../admin_cad/dashboard_cad.php">Acessar Painel CAD</a>';
        }
        if ($tipoServidor === 'COEN' || $setorAdmin === 'COEN') {
            echo '<a class="btn-menu" href="../admin_coen/dashboard_coen.php">Acessar Painel COEN</a>';
        }
    ?>
    <form id="form-solicitacao" enctype="multipart/form-data">
        <label>Arquivo para impressão
            <input type="file" name="arquivo" id="arquivo" required accept=".pdf,.doc,.docx,.jpg,.png">
        </label>
        <label class="toggle-switch-label">
            <span>Solicitar cópia no balcão</span>
            <div class="toggle-switch">
                <input type="checkbox" id="solicitar_balcao" name="solicitar_balcao">
                <span class="slider"></span>
            </div>
        </label>
        <label>Quantidade de cópias
            <input type="number" name="qtd_copias" id="qtd_copias" min="1" max="100" required>
        </label>
        <label>Número de páginas
            <input type="number" name="qtd_paginas" id="qtd_paginas" min="1" max="500" required placeholder="Informe o nº de páginas">
        </label>
        <div id="preview-copias-paginas" style="margin:0.5em 0;font-weight:bold;color:#2a3b4b;"></div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qtdCopiasInput = document.getElementById('qtd_copias');
            const qtdPaginasInput = document.getElementById('qtd_paginas');
            const previewDiv = document.getElementById('preview-copias-paginas');

            function atualizarPreview() {
            const copias = parseInt(qtdCopiasInput.value, 10) || 0;
            const paginas = parseInt(qtdPaginasInput.value, 10) || 0;
            if (copias > 0 && paginas > 0) {
                previewDiv.textContent = `Total: ${copias} cópias x ${paginas} páginas = ${copias * paginas} impressões`;
            } else {
                previewDiv.textContent = '';
            }
            }

            qtdCopiasInput.addEventListener('input', atualizarPreview);
            qtdPaginasInput.addEventListener('input', atualizarPreview);
        });
        </script>
        <label>Tipo de impressão
            <select name="tipo_impressao" id="tipo_impressao" required>
                <option value="pb">Preto e Branco</option>
                <option value="colorida">Colorida</option>
            </select>
        </label>
        <button type="submit">Enviar Solicitação</button>
    </form>

    <section id="status-solicitacoes">
        <div class="section-header">
            <h2>Minhas Solicitações Recentes</h2>
        </div>
        <div id="tabela-solicitacoes"></div>
    </section>

    <button onclick="window.location.href='historico_solicitacoes.php'">Ver Histórico Completo</button>
</main>
<script src="dashboard_servidor.js"></script>
<?php require_once '../../includes/footer.php'; ?>
