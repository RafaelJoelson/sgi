<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// Verifica se é edição e busca os dados do aluno
$modo_edicao = isset($_GET['matricula']) && !empty($_GET['matricula']);
$aluno = null;
$turma_aluno_id = null;

if ($modo_edicao) {
    $stmt = $conn->prepare("SELECT * FROM Aluno WHERE matricula = :matricula");
    $stmt->execute([':matricula' => $_GET['matricula']]);
    $aluno = $stmt->fetch();

    // Se estiver editando, busca o turma_id correspondente ao cota_id do aluno
    if ($aluno && $aluno->cota_id) {
        $stmt_turma_aluno = $conn->prepare("SELECT turma_id FROM CotaAluno WHERE id = :cota_id");
        $stmt_turma_aluno->execute([':cota_id' => $aluno->cota_id]);
        $turma_aluno_id = $stmt_turma_aluno->fetchColumn();
    }
}

// MUDANÇA: Busca todas as turmas disponíveis para o select
$stmt_turmas = $conn->query(
    "SELECT t.id, t.periodo, c.sigla, c.nome_completo 
     FROM Turma t
     JOIN Curso c ON t.curso_id = c.id
     ORDER BY c.nome_completo ASC, t.id ASC"
);
$turmas_disponiveis = $stmt_turmas->fetchAll();

// Buscar semestre letivo vigente para a data de validade
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT data_fim FROM SemestreLetivo WHERE :hoje BETWEEN data_inicio AND data_fim ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();
$data_validade_padrao = $aluno->data_fim_validade ?? ($semestre_vigente->data_fim ?? '');

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="form_aluno.css?v=<?= ASSET_VERSION ?>">
<main>
<div class="container-principal"> <!-- Um container para o conteúdo -->
        <?php
        // Chama a função de migalhas se o usuário estiver logado
        if (isset($_SESSION['usuario'])) {
            gerar_migalhas();
        }
        ?>
    <h1><?= $modo_edicao ? 'Editar Aluno' : 'Cadastrar Novo Aluno' ?></h1>

    <?php if (isset($_SESSION['mensagem_erro'])): ?>
        <div class="mensagem-erro" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_erro']) ?></div>
        <?php unset($_SESSION['mensagem_erro']); ?>
    <?php endif; ?>

    <form action="<?= $modo_edicao ? 'processar_edicao_aluno.php' : 'processar_cadastro_aluno.php' ?>" method="POST" class="form-aluno">
        <?php if ($modo_edicao): ?>
            <input type="hidden" name="matricula" value="<?= htmlspecialchars($aluno->matricula) ?>">
        <?php endif; ?>

        <label>*Matrícula
            <input type="text" name="matricula" maxlength="10" required value="<?= $aluno->matricula ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>
        <label>*Nome
            <input type="text" name="nome" required value="<?= $aluno->nome ?? '' ?>">
        </label>
        <label>*Sobrenome
            <input type="text" name="sobrenome" required value="<?= $aluno->sobrenome ?? '' ?>">
        </label>
        <label>*E-mail
            <input type="email" name="email" required value="<?= $aluno->email ?? '' ?>">
        </label>
        <label>*CPF
            <input type="text" name="cpf" required maxlength="11" pattern="\d{11}" title="Digite os 11 números do CPF" value="<?= $aluno->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>

        <?php if (!$modo_edicao): ?>
            <label>Senha
                <input type="password" name="senha" required minlength="6">
            </label>
        <?php endif; ?>

        <label>*Cargo
            <select name="cargo" required>
                <option value="Nenhum" <?= (isset($aluno) && $aluno->cargo === 'Nenhum') ? 'selected' : '' ?>>Nenhum</option>
                <option value="Líder" <?= (isset($aluno) && $aluno->cargo === 'Líder') ? 'selected' : '' ?>>Líder</option>
                <option value="Vice-líder" <?= (isset($aluno) && $aluno->cargo === 'Vice-líder') ? 'selected' : '' ?>>Vice-líder</option>
            </select>
        </label>

        <!-- MUDANÇA: O select agora lista Turmas e envia 'turma_id' -->
        <label>*Turma
            <select name="turma_id" required>
                <option value="" disabled <?= !$modo_edicao ? 'selected' : '' ?>>Selecione uma turma</option>
                <?php foreach ($turmas_disponiveis as $turma): ?>
                    <option value="<?= $turma->id ?>" <?= ($modo_edicao && $turma_aluno_id == $turma->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($turma->nome_completo) ?> (<?= htmlspecialchars($turma->sigla) ?>) - <?= htmlspecialchars($turma->periodo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Data Fim da Validade
            <input type="date" name="data_fim_validade" value="<?= htmlspecialchars($data_validade_padrao) ?>" readonly>
        </label>

        <?php if ($modo_edicao): ?>
            <label class="checkbox-label">
                <input type="checkbox" name="ativo" value="1" <?= (isset($aluno) && $aluno->ativo) ? 'checked' : '' ?>>
                Aluno Ativo
            </label>
        <?php endif; ?>

        <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Aluno' ?></button>
    </form>
    <a class="btn-back" href="dashboard_cad.php">&larr; Voltar</a>
</main>
<?php include_once '../../includes/footer.php'; ?>
