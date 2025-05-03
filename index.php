<?php
// Inclui configurações
require_once 'includes/config.php';

// Define o título da página
$pageTitle = 'Página Inicial';

// Inclui o cabeçalho
require_once 'includes/header.php';

// Inclui a navegação
require_once 'includes/nav.php';
?>

<main>
    <section class="login-container">
        <h2>Login</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert error">
                <?php
                $errors = [
                    'invalid' => 'Usuário ou senha inválidos',
                    'empty' => 'Preencha todos os campos',
                    'restricted' => 'Área restrita - faça login'
                ];
                echo $errors[$_GET['error']] ?? 'Erro ao fazer login';
                ?>
            </div>
        <?php endif; ?>

        <form action="includes/login-process.php" method="POST">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>
        
        <div class="login-links">
            <a href="forgot-password.php">Esqueci minha senha</a>
        </div>
    </section>
    <section>
        <div>
            <div>
                <img src="" alt="">
            </div>
            <div>
                <h2></h2>
            </div>
        </div>
    </section>
</main>
<?php
// Inclui o rodapé
require_once 'includes/footer.php';
?>