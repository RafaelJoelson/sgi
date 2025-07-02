<?php
session_start(); // Adicione no topo para gerenciar mensagens de erro

// Redireciona usuário logado para seu dashboard
if (isset($_SESSION['usuario'])) {
    $tipo = $_SESSION['usuario']['tipo'] ?? '';
    if ($tipo === 'aluno') {
        header('Location: pages/aluno/dashboard_aluno.php');
        exit;
    } elseif ($tipo === 'servidor') {
        if (!empty($_SESSION['usuario']['setor_admin'])) {
            if ($_SESSION['usuario']['setor_admin'] === 'CAD') {
                header('Location: pages/admin_cad/dashboard_cad.php');
                exit;
            } elseif ($_SESSION['usuario']['setor_admin'] === 'COEN') {
                header('Location: pages/admin_coen/dashboard_coen.php');
                exit;
            }
        }
        header('Location: pages/servidor/dashboard_servidor.php');
        exit;
    } elseif ($tipo === 'reprografo') {
        header('Location: pages/reprografo/dashboard_reprografo.php');
        exit;
    }
}

// Inclui configurações de conexão
require_once 'includes/config.php';

// Define o título da página
$pageTitle = 'Login - Sistema de Impressões';

// Inclui o cabeçalho
require_once 'includes/header.php';

// Inclui a navegação (se necessário)
require_once 'includes/nav.php';
?>

<main class="main-login-container">
    <section class="row justify-content-center">
        <div class="col-md-6 login-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="text-center mb-0">Por favor, realize o Login</h2>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['login_erro'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlspecialchars($_SESSION['login_erro']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['login_erro']); ?>
                    <?php endif; ?>

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