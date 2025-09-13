<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO: Apenas Super Administradores dos setores CAD ou COEN podem acessar.
if (
    !isset($_SESSION['usuario']) || 
    $_SESSION['usuario']['tipo'] !== 'servidor' ||
    empty($_SESSION['usuario']['is_super_admin']) || 
    !in_array($_SESSION['usuario']['setor_admin'], ['CAD', 'COEN'])
) {
    echo json_encode(['Acesso negado.']);
    exit;
}

$feedback_message = '';
$feedback_type = '';

// 2. PROCESSAMENTO DAS AÇÕES DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    try {
        $conn->beginTransaction();

        if ($_POST['acao'] === 'reset_repro' && isset($_POST['repro_id'])) {
            $repro_id = (int)$_POST['repro_id'];
            
            // Gera uma senha aleatória forte
            $nova_senha = bin2hex(random_bytes(6));
            $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE Reprografia SET senha = :senha WHERE id = :id");
            $stmt->execute([':senha' => $hash_senha, ':id' => $repro_id]);

            $feedback_message = "Senha do operador ID {$repro_id} redefinida para: <strong>{$nova_senha}</strong>. Anote e compartilhe de forma segura.";
            $feedback_type = 'success';
        }

        // MUDANÇA: Lógica para alterar o login da reprografia
        if ($_POST['acao'] === 'change_repro_login' && isset($_POST['repro_id'], $_POST['novo_login'])) {
            $repro_id = (int)$_POST['repro_id'];
            $novo_login = trim($_POST['novo_login']);

            if (empty($novo_login)) {
                throw new Exception("O novo login não pode estar vazio.");
            }

            // Verifica se o novo login já está em uso por outro operador
            $stmt_check = $conn->prepare("SELECT id FROM Reprografia WHERE login = :login AND id != :id");
            $stmt_check->execute([':login' => $novo_login, ':id' => $repro_id]);
            if ($stmt_check->fetch()) {
                throw new Exception("Este login já está em uso por outro operador.");
            }

            // Atualiza o login
            $stmt = $conn->prepare("UPDATE Reprografia SET login = :login WHERE id = :id");
            $stmt->execute([':login' => $novo_login, ':id' => $repro_id]);

            $feedback_message = "Login do operador ID {$repro_id} alterado para: <strong>" . htmlspecialchars($novo_login) . "</strong>.";
            $feedback_type = 'success';
        }

        if ($_POST['acao'] === 'reset_super_admins') {
            $senha_padrao = 'Admin@123';
            $hash_padrao = password_hash($senha_padrao, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "UPDATE Usuario u JOIN Servidor s ON u.id = s.usuario_id 
                 SET u.senha = :senha WHERE s.is_super_admin = TRUE"
            );
            $stmt->execute([':senha' => $hash_padrao]);
            
            $feedback_message = "A senha de todos os Super Administradores foi redefinida para o padrão: <strong>{$senha_padrao}</strong>.";
            $feedback_type = 'success';
        }
        
        $conn->commit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $feedback_message = "Ocorreu um erro: " . $e->getMessage();
        $feedback_type = 'error';
    }
}

// Busca a lista de operadores da reprografia para exibir na página
$Reprografias = $conn->query("SELECT id, login, nome, sobrenome FROM Reprografia ORDER BY nome")->fetchAll();

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="../admin_cad/css/dashboard_cad.css?v=<?= ASSET_VERSION ?>">
<style>
    .maintenance-container { max-width: 900px; margin: 2rem auto; }
    .card-maintenance { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 2rem; }
    .card-maintenance h2 { color: #0056b3; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
    .repro-list li { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #eee; }
    .repro-list li:last-child { border-bottom: none; }
    .btn-reset { background-color: #ffc107; color: #212529; padding: 0.3rem 0.8rem; border-radius: 5px; text-decoration: none; border: none; cursor: pointer; }
    .btn-danger-zone { background-color: #dc3545; color: white; width: 100%; padding: 1rem; font-size: 1.1rem; }
    .feedback { padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; }
    .feedback.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .feedback.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-inline { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
    .form-inline .form-group { display: flex; flex-direction: column; }
</style>

<div class="maintenance-container">
    <?php if (isset($_SESSION['usuario'])) { gerar_migalhas(); } ?>
    
    <?php if ($feedback_message): ?>
        <div class="feedback <?= $feedback_type ?>"><?= $feedback_message ?></div>
    <?php endif; ?>

    <!-- Seção para Alterar Login da Reprografia -->
    <div class="card-maintenance">
        <h2><i class="fas fa-user-edit"></i> Alterar Login da Reprografia</h2>
        <p>Selecione o operador e digite o novo login desejado.</p>
        <form method="POST" class="form-inline">
            <input type="hidden" name="acao" value="change_repro_login">
            <div class="admin-form-group">
                <label for="repro_id_login">Operador:</label>
                <select name="repro_id" id="repro_id_login" class="form-control" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($Reprografias as $repro): ?>
                        <option value="<?= $repro->id ?>"><?= htmlspecialchars($repro->nome . ' ' . $repro->sobrenome) ?> (<?= htmlspecialchars($repro->login) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label for="novo_login">Novo Login:</label>
                <input type="text" name="novo_login" id="novo_login" class="form-control" required>
            </div>
            <button type="submit" class="btn-menu" style="background-color: #17a2b8;">Salvar Novo Login</button>
        </form>
    </div>

    <!-- Seção para Redefinir Senhas da Reprografia -->
    <div class="card-maintenance">
        <h2><i class="fas fa-key"></i> Redefinir Senha da Reprografia</h2>
        <p>Clique no botão para gerar uma nova senha aleatória para o operador. A nova senha será exibida na tela.</p>
        <ul class="repro-list">
            <?php foreach ($Reprografias as $repro): ?>
                <li>
                    <span><?= htmlspecialchars($repro->nome . ' ' . $repro->sobrenome) ?> (Login: <?= htmlspecialchars($repro->login) ?>)</span>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="acao" value="reset_repro">
                        <input type="hidden" name="repro_id" value="<?= $repro->id ?>">
                        <button type="submit" class="btn-reset">Redefinir Senha</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Seção de "Zona de Perigo" para ações críticas -->
    <div class="card-maintenance">
        <h2 style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h2>
        <p>As ações abaixo são irreversíveis e devem ser usadas apenas em emergências.</p>
        <form method="POST" onsubmit="return confirm('ATENÇÃO: Esta ação é irreversível e afetará TODOS os super administradores. Deseja continuar?');">
            <input type="hidden" name="acao" value="reset_super_admins">
            <button type="submit" class="btn-danger-zone">Redefinir Senha de TODOS Super Admins para "Senha Padrão"</button>
        </form>
    </div>

    <!-- Seção de Verificação do Sistema -->
    <div class="card-maintenance">
        <h2><i class="fas fa-check-circle"></i> Verificação do Sistema</h2>
        <ul class="repro-list">
            <li><span>Status da Conexão com o Banco de Dados:</span> <strong style="color: #28a745;">OK</strong></li>
            <li><span>Versão do PHP:</span> <strong><?= phpversion() ?></strong></li>
        </ul>
        <a class="btn-menu" href="simular_cron.php">Simular Cron</a>
    </div>
    
    <a class="btn-back" href="../../index.php">&larr; Voltar</a>
</div>

<?php include_once '../../includes/footer.php'; ?>
