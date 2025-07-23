<?php
// Dashboard da Reprografia
session_start();
require_once '../../includes/config.php';
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    header('Location: ../../reprografia.php');
    exit;
}
// Limpa arquivos da pasta uploads com mais de 15 dias
$diretorioUploads = realpath(__DIR__ . '/../../uploads');
if ($diretorioUploads && is_dir($diretorioUploads)) {
    $arquivos = scandir($diretorioUploads);
    $agora = time();
    $dias = 15 * 24 * 60 * 60; // 15 dias em segundos

    foreach ($arquivos as $arquivo) {
        $caminho = $diretorioUploads . DIRECTORY_SEPARATOR . $arquivo;
        if (is_file($caminho)) {
            $modificadoHa = $agora - filemtime($caminho);
            if ($modificadoHa > $dias) {
                @unlink($caminho);
            }
        }
    }
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="./css/dashboard_reprografia.css?v=<?= ASSET_VERSION ?>">
<div class="dashboard-layout">
    <aside class="dashboard-aside-repro">
        <div class="container-principal">
        <?php
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
        <nav class="dashboard-menu">
            <img src="../../img/logo_reprografia.png" alt="Logo da Reprografia">
            <a href="dashboard_reprografia.php" class="dashboard-menu-link active">Solicitações Pendentes</a>
            <a href="relatorio_reprografia.php" class="dashboard-menu-link">Relatórios</a>
            <a href="#" id="btn-alterar-dados" class="dashboard-menu-link">Alterar Meus Dados</a>
            <hr>
            <a href="limpar_uploads.php" class="dashboard-menu-link"
               onclick="return confirm('Tem certeza que deseja limpar a pasta uploads? Esta ação é irreversível!') && confirm('Confirme novamente: deseja realmente apagar TODOS os arquivos da pasta uploads?');">
                <i class="fas fa-trash-alt"></i> Limpar Pasta Uploads
            </a>
        </nav>
        </div>
    </aside>
    <main class="dashboard-main-repro">
        <div id="toast-notification-container"></div>
        <h2>Painel da Reprografia</h2>
        <section id="solicitacoes-pendentes">
            <div class="section-header">
                <h2>Solicitações Pendentes</h2>
                <button id="btn-ativar-notificacoes" class="btn-notificacao" title="Clique para permitir notificações no navegador">
                    <i class="fas fa-bell"></i> Ativar Notificações
                </button>
            </div>
            <div id="tabela-solicitacoes" class="table-responsive"></div>
        </section>
    </main>
</div>

<!-- Modal para editar dados do reprografo -->
<div id="modal-editar-dados" class="modal">
    <div class="modal-content">
        <span class="close" id="close-modal-editar">&times;</span>
        <h2>Alterar Meus Dados</h2>
        <form id="form-editar-reprografia" enctype="multipart/form-data">
            <div class="form-editar-form-group">
                <div id="mensagem-modal-erro" class="mensagem-erro" style="display: none;"></div>
                <input type="hidden" id="reprografia-id" name="id">
                <label>Logo da Reprografia (PNG ou WEBP)
                    <input type="file" id="reprografia-logo" name="logo" accept=".png,.webp,image/png,image/webp">
                </label>
                <label>Login
                    <input type="text" id="reprografia-login" name="login" readonly disabled style="background-color: #e9ecef;">
                </label>
                <label>Nome
                    <input type="text" id="reprografia-nome" name="nome" required>
                </label>
                <label>Sobrenome
                    <input type="text" id="reprografia-sobrenome" name="sobrenome" required>
                </label>
            </div>
            <div class="form-editar-form-group">
                <label>Email
                    <input type="email" id="reprografia-email" name="email">
                </label>
                <label>Nova Senha
                    <input type="password" id="reprografia-nova-senha" name="nova_senha" minlength="6">
                </label>
                <label>Confirmar Nova Senha
                    <input type="password" id="reprografia-confirma-senha" name="confirma_senha" minlength="6">
                </label>
                <p>Deixe os campos de senha em branco para não alterá-la.</p>
                <div class="button-container">
                    <button type="submit">Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="dashboard_reprografia.js?v=<?= ASSET_VERSION ?>"></script>
<?php require_once '../../includes/footer.php'; ?>
