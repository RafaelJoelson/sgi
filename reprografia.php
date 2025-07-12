<?php
session_start();
require_once 'includes/config.php';

// Redireciona se já estiver logado
if (isset($_SESSION['usuario']) && $_SESSION['usuario']['tipo'] === 'reprografo') {
    header('Location: pages/reprografo/dashboard_reprografo.php');
    exit;
}

$pageTitle = 'Login - Reprografia';
include_once 'includes/header.php';
?>

<main class="main-login-container">
    <section class="row justify-content-center">
        <div class="col-md-6 login-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="text-center mb-0">Reprografia</h2>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['erro_login'])): ?>
                        <div class="alert alert-danger" style="color: red;" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($_SESSION['erro_login']) ?>
                        </div>
                        <?php unset($_SESSION['erro_login']); ?>
                    <?php endif; ?>

                    <!-- O formulário agora aponta para o novo script de processo -->
                    <form action="./includes/login_process_repro.php" method="POST">
                        <div class="form-group">
                            <!-- Campo alterado de CPF para Login -->
                            <label for="login">Login:</label>
                            <input type="text" id="login" name="login" 
                                   class="form-control" 
                                   placeholder="Digite seu usuário"
                                   required>
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

<?php
include_once 'includes/footer.php';
?>
