<?php
// Dashboard do Aluno
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/dashboard_aluno.css?v=<?= ASSET_VERSION ?>">
<main class="container">
    <div id="toast-notification-container"></div>
    <button id="btn-ativar-notificacoes" class="btn-notificacao" title="Clique para permitir notificações no navegador">
        <i class="fas fa-bell"></i> Ativar Notificações
    </button>
    <!-- Verifica horário de funcionamento -->
    <?php
    date_default_timezone_set('America/Sao_Paulo');
    $horaAtual = (int)date('G');
    if ($horaAtual < HORARIO_FUNC_INICIO || $horaAtual >= HORARIO_FUNC_FIM): ?>
        <div class="alert alert-warning" style="margin:1em 0;padding:1em;border:1px solid #f0ad4e;background:#fff8e1;color:#856404;">
            <strong>Atenção:</strong> Você está fora do horário de funcionamento da reprografia (17h às 21h). 
            Qualquer solicitação enviada agora só será aceita quando a reprografia abrir.
        </div>
    <?php endif; ?>
    <h4>Aluno(a): <?= htmlspecialchars($_SESSION['usuario']['nome'] . (isset($_SESSION['usuario']['sobrenome']) ? ' ' . $_SESSION['usuario']['sobrenome'] : '')) ?></h4>
    <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
    
    <form id="form-solicitacao" enctype="multipart/form-data">
        <span class="file-hint" id="file-hint">
        </span>
        <span class="file-label-text file-hint" id="file-hint">Arquivo para impressão    
            (Dica) <i class="fas fa-info-circle" title="Dica: Dê preferência para PDFs, as páginas são contabilizadas automaticamente."></i>
            <span class="hint-text"> Dê preferência para PDFs, as páginas são contabilizadas automaticamente.</span>
        </span>
        <label for="arquivo" class="file-label">
            <input type="file" name="arquivo" id="arquivo" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.odt" aria-describedby="file-hint">
        </label>
        <label class="toggle-switch-label">
            <span>Solicitar cópia no balcão</span>
            <div class="toggle-switch">
                <input type="checkbox" id="solicitar_balcao" name="solicitar_balcao">
                <span class="slider"></span>
            </div>
        </label>
        <label>*Quantidade de cópias ou impressões
            <input type="number" name="qtd_copias" id="qtd_copias" min="1" max="100" required>
        </label>
        <label>*Número de páginas do arquivo
            <input type="number" name="qtd_paginas" id="qtd_paginas" min="1" max="500" required placeholder="Nº de páginas do arquivo">
        </label>
        <div id="preview-copias-paginas" style="margin:0.5em 0;font-weight:bold;color:#2a3b4b;"></div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.9.359/pdf.min.js"></script>
<script src="./js/dashboard_aluno.js?v=<?= ASSET_VERSION ?>"></script>
<?php require_once '../../includes/footer.php'; ?>