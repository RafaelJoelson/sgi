<?php
session_start(); // Adicione no topo para gerenciar mensagens de erro

// Inclui configurações de conexão
require_once 'includes/config.php';

// Define o título da página
$pageTitle = 'Login - Sistema de Impressões';

// Inclui o cabeçalho
require_once 'includes/header.php';

// Inclui a navegação (se necessário)
require_once 'includes/nav.php';
?>

<main class="container">
    <section class="row justify-content-center">
        <div class="col-md-6 login-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="text-center mb-0">Login do Sistema</h2>
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
                    
                    <div class="text-center mt-3">
                        <a href="./pages/admin/gerar_senha.php" class="text-secondary">
                            <i class="fas fa-key"></i> Esqueci minha senha
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Máscara para CPF (opcional)
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    e.target.value = value;
});
</script>

<?php
// Inclui o rodapé
require_once 'includes/footer.php';
?>