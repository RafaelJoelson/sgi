<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexão com o banco
require_once '../includes/config.php';

session_start();

if (
    !isset($_SESSION['usuario_id']) ||          
    !isset($_SESSION['usuario_tipo']) ||        
    $_SESSION['usuario_tipo'] !== 'S' ||        
    empty($_SESSION['is_admin'])                
) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Inclui header
require_once '../includes/header.php';

// Funções CRUD
require_once '../includes/admin_functions.php';

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cadastrar_usuario':
                cadastrarUsuario($conn, $_POST);
                break;
            case 'editar_usuario':
                editarUsuario($conn, $_POST);
                break;
            case 'excluir_usuario':
                excluirUsuario($conn, $_POST['id'], $_POST['tipo']);
                break;
            case 'gerenciar_cotas':
                gerenciarCotas($conn, $_POST);
                break;
            case 'resetar_senha':
                resetarSenha($conn, $_POST['id']);
                break;
        }
    }
}

// Busca
$resultados = [];
if (isset($_GET['busca'])) {
    $termo = $_GET['termo'] ?? '';
    $tipo = $_GET['tipo'] ?? 'todos';
    
    $resultados = buscarUsuarios($conn, $termo, $tipo);
}
?>

<main class="container mt-5">
    <h2 class="mb-4">Gerenciamento de Usuários</h2>
    
    <!-- Formulário de Busca -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" name="termo" class="form-control" placeholder="Buscar por CPF, matrícula ou nome..." value="<?= $_GET['termo'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="tipo" class="form-control">
                            <option value="todos" <?= ($_GET['tipo'] ?? '') === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="aluno" <?= ($_GET['tipo'] ?? '') === 'aluno' ? 'selected' : '' ?>>Alunos</option>
                            <option value="servidor" <?= ($_GET['tipo'] ?? '') === 'servidor' ? 'selected' : '' ?>>Servidores</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="busca" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Botão de Cadastro -->
    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#modalCadastro">
        <i class="fas fa-plus"></i> Cadastrar Novo Usuário
    </button>

    <!-- Resultados da Busca -->
    <?php if (!empty($resultados)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>CPF</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Identificador</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $usuario): ?>
                <tr>
                    <td><?= formatarCPF($usuario['cpf']) ?></td>
                    <td><?= htmlspecialchars($usuario['nome']) ?></td>
                    <td><?= $usuario['tipo'] === 'A' ? 'Aluno' : 'Servidor' ?></td>
                    <td>
                        <?= $usuario['tipo'] === 'A' 
                            ? 'Mat: ' . htmlspecialchars($usuario['matricula'] ?? '')
                            : 'SIAP: ' . htmlspecialchars($usuario['siap'] ?? '') ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info btn-editar" data-id="<?= $usuario['id'] ?>" data-tipo="<?= $usuario['tipo'] ?>">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        
                        <?php if ($usuario['tipo'] === 'A'): ?>
                        <button class="btn btn-sm btn-warning btn-cotas" data-id="<?= $usuario['id'] ?>" data-turma="<?= htmlspecialchars($usuario['turma'] ?? '') ?>">
                            <i class="fas fa-coins"></i> Cotas
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-sm btn-danger btn-excluir" data-id="<?= $usuario['id'] ?>" data-tipo="<?= $usuario['tipo'] ?>">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </td>
                </tr>
                <!-- Linha de detalhes (hidden) -->
                <tr class="detalhes-usuario" id="detalhes-<?= $usuario['id'] ?>" style="display: none;">
                    <td colspan="5">
                        <div class="p-3 bg-light">
                            <form method="POST" action="" class="form-editar">
                                <input type="hidden" name="action" value="editar_usuario">
                                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                <input type="hidden" name="tipo" value="<?= $usuario['tipo'] ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nome Completo</label>
                                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($usuario['tipo'] === 'A'): ?>
                                            <div class="form-group">
                                                <label>Matrícula</label>
                                                <input type="text" name="matricula" class="form-control" value="<?= htmlspecialchars($usuario['matricula'] ?? '') ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Turma</label>
                                                <input type="text" name="turma" class="form-control" value="<?= htmlspecialchars($usuario['turma'] ?? '') ?>" required>
                                            </div>
                                        <?php else: ?>
                                            <div class="form-group">
                                                <label>SIAP</label>
                                                <input type="text" name="siap" class="form-control" value="<?= htmlspecialchars($usuario['siap'] ?? '') ?>" required>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="is_admin" class="form-check-input" id="admin-<?= $usuario['id'] ?>" <?= ($usuario['is_admin'] ?? false) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="admin-<?= $usuario['id'] ?>">Administrador</label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-save"></i> Salvar Alterações
                                </button>
                                
                                <button type="button" class="btn btn-secondary btn-reset-senha" data-id="<?= $usuario['id'] ?>">
                                    <i class="fas fa-key"></i> Redefinir Senha
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif (isset($_GET['busca'])): ?>
        <div class="alert alert-info">Nenhum resultado encontrado.</div>
    <?php else: ?>
        <div class="alert alert-secondary">Digite um termo de busca para listar os usuários.</div>
    <?php endif; ?>
</main>

<!-- Modal de Cadastro -->
<div class="modal fade" id="modalCadastro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastrar Novo Usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="cadastrar_usuario">
                    
                    <ul class="nav nav-tabs" id="tabsCadastro" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="aluno-tab" data-toggle="tab" href="#cadastro-aluno" role="tab">Aluno</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="servidor-tab" data-toggle="tab" href="#cadastro-servidor" role="tab">Servidor</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3 border border-top-0 rounded-bottom">
                        <!-- Cadastro de Aluno -->
                        <div class="tab-pane fade show active" id="cadastro-aluno" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>CPF (somente números)</label>
                                        <input type="text" name="cpf_aluno" class="form-control" required pattern="\d{11}">
                                    </div>
                                    <div class="form-group">
                                        <label>Nome Completo</label>
                                        <input type="text" name="nome_aluno" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Matrícula</label>
                                        <input type="text" name="matricula" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Turma</label>
                                        <input type="text" name="turma" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email_aluno" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Senha Temporária</label>
                                        <input type="password" name="senha_aluno" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" name="is_lider" class="form-check-input" id="is_lider">
                                <label class="form-check-label" for="is_lider">Líder de Turma</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" name="is_vice" class="form-check-input" id="is_vice">
                                <label class="form-check-label" for="is_vice">Vice-Líder</label>
                            </div>
                        </div>
                        
                        <!-- Cadastro de Servidor -->
                        <div class="tab-pane fade" id="cadastro-servidor" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>CPF (somente números)</label>
                                        <input type="text" name="cpf_servidor" class="form-control" required pattern="\d{11}">
                                    </div>
                                    <div class="form-group">
                                        <label>Nome Completo</label>
                                        <input type="text" name="nome_servidor" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>SIAP</label>
                                        <input type="text" name="siap" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email_servidor" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Senha Temporária</label>
                                        <input type="password" name="senha_servidor" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4 pt-2">
                                        <input type="checkbox" name="is_admin" class="form-check-input" id="is_admin">
                                        <label class="form-check-label" for="is_admin">Administrador</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Cotas -->
<div class="modal fade" id="modalCotas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerenciar Cotas da Turma</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="gerenciar_cotas">
                <input type="hidden" name="turma" id="inputTurma">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Semestre (AAAA.S)</label>
                        <input type="text" name="semestre" class="form-control" pattern="\d{4}\.[12]" required>
                    </div>
                    <div class="form-group">
                        <label>Total de Cotas</label>
                        <input type="number" name="total_cotas" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Data de Validade</label>
                        <input type="date" name="data_validade" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Cotas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.</p>
                <form method="POST" action="" id="formExcluir">
                    <input type="hidden" name="action" value="excluir_usuario">
                    <input type="hidden" name="id" id="inputExcluirId">
                    <input type="hidden" name="tipo" id="inputExcluirTipo">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="formExcluir" class="btn btn-danger">Confirmar Exclusão</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Redefinição de Senha -->
<div class="modal fade" id="modalResetSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Redefinir Senha</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="resetar_senha">
                <input type="hidden" name="id" id="inputResetId">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nova Senha Temporária</label>
                        <input type="password" name="nova_senha" class="form-control" required>
                    </div>
                    <div class="alert alert-info">
                        O usuário será obrigado a alterar esta senha no próximo login.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Redefinir Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Controle dos modais e ações
$(document).ready(function() {
    // Botão Editar - Mostra/Esconde detalhes
    $('.btn-editar').click(function() {
        const id = $(this).data('id');
        $(`#detalhes-${id}`).toggle();
    });
    
    // Botão Cotas - Abre modal
    $('.btn-cotas').click(function() {
        $('#inputTurma').val($(this).data('turma'));
        $('#modalCotas').modal('show');
    });
    
    // Botão Excluir - Abre confirmação
    $('.btn-excluir').click(function() {
        $('#inputExcluirId').val($(this).data('id'));
        $('#inputExcluirTipo').val($(this).data('tipo'));
        $('#modalConfirmacao').modal('show');
    });
    
    // Botão Resetar Senha
    $('.btn-reset-senha').click(function() {
        $('#inputResetId').val($(this).data('id'));
        $('#modalResetSenha').modal('show');
    });
    
    // Formata CPF na exibição
    $('.cpf-format').each(function() {
        const cpf = $(this).text();
        if (cpf.length === 11) {
            $(this).text(cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4'));
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>