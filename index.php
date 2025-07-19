<?php
session_start(); 

// CORREÇÃO: Lógica de redirecionamento para usuário já logado
if (isset($_SESSION['usuario'])) {
    $tipo = $_SESSION['usuario']['tipo'] ?? '';
    $setor_admin = $_SESSION['usuario']['setor_admin'] ?? '';
    $is_admin = $_SESSION['usuario']['is_admin'] ?? false;

    $redirect_url = '';

    if ($tipo === 'aluno') {
        $redirect_url = 'pages/aluno/dashboard_aluno.php';
    } elseif ($tipo === 'reprografo') {
        $redirect_url = 'pages/reprografo/dashboard_reprografo.php';
    } elseif ($tipo === 'servidor') {
        if ($is_admin) {
            if ($setor_admin === 'CAD') {
                $redirect_url = 'pages/admin_cad/dashboard_cad.php';
            } elseif ($setor_admin === 'COEN') {
                $redirect_url = 'pages/admin_coen/dashboard_coen.php';
            } else {
                // Um admin sem setor específico pode ir para um painel genérico
                $redirect_url = 'pages/servidor/dashboard_servidor.php';
            }
        } else {
            $redirect_url = 'pages/servidor/dashboard_servidor.php';
        }
    }

    if ($redirect_url) {
        header('Location: ' . $redirect_url);
        exit;
    }
}

// Inclui configurações de conexão
require_once 'includes/config.php';

// Define o título da página
$pageTitle = 'Login - Sistema de Impressões';

// Inclui o cabeçalho
require_once 'includes/header.php';

?>

<main class="main-login-container">
    <section class="row justify-content-center">
        <div class="col-md-6 login-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="text-center mb-0">Por favor, realize o Login</h2>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['erro_login'])): ?>
                        <div class="alert alert-danger" style="color: red;" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($_SESSION['erro_login']) ?>
                        </div>
                        <?php unset($_SESSION['erro_login']); ?>
                    <?php endif; ?>
                    <div class="logo-container">
                        <img src="./img/logo_sgi.png" alt="Logo do Sistema de Impressões" class="logo-login">                                              
                    </div>
                    <form action="./includes/login_process.php" method="POST">
                        <div class="form-group">
                            <label for="cpf">CPF (apenas números):</label>
                            <input type="text" id="cpf" name="cpf" 
                                   class="form-control" 
                                   pattern="\d{11}" 
                                   maxlength="11"
                                   title="Digite os 11 números do CPF"
                                   required>
                            <small class="form-text text-muted">Ex: 12345678901</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Senha:</label>
                            <input type="password" id="password" name="senha" 
                                   class="form-control" 
                                   minlength="6"
                                   required>
                        </div>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary btn-block, btn-login">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Máscara para CPF e limitação de 11 dígitos
const cpfInput = document.getElementById('cpf');
cpfInput.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);
    e.target.value = value;
});
</script>

<?php
// Inclui o rodapé
require_once 'includes/footer.php';
?>
