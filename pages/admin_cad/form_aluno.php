<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// Verifica se é edição
$modo_edicao = isset($_GET['matricula']);
$aluno = null;

if ($modo_edicao) {
    $stmt = $conn->prepare("SELECT * FROM Aluno WHERE matricula = :matricula");
    $stmt->bindParam(':matricula', $_GET['matricula']);
    $stmt->execute();
    $aluno = $stmt->fetch();
}

// Buscar cotas disponíveis
$stmt = $conn->query("
    SELECT ca.id, t.periodo, c.sigla, c.nome_completo 
    FROM CotaAluno ca
    JOIN Turma t ON ca.turma_id = t.id
    JOIN Curso c ON t.curso_id = c.id
    ORDER BY c.nome_completo ASC, t.periodo ASC
");
$cotas = $stmt->fetchAll();

// Buscar semestre letivo vigente para exibir ao usuário (opcional)
$hoje = date('Y-m-d');
$stmt_semestre = $conn->prepare("SELECT * FROM SemestreLetivo WHERE data_inicio <= :hoje AND data_fim >= :hoje ORDER BY data_fim DESC LIMIT 1");
$stmt_semestre->execute([':hoje' => $hoje]);
$semestre_vigente = $stmt_semestre->fetch();

?>
<?php include_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="form_aluno.css">
<main>
    <h1><?= $modo_edicao ? 'Editar Aluno' : 'Cadastrar Novo Aluno' ?></h1>

    <!-- MUDANÇA: Exibe mensagens de sucesso ou erro da sessão -->
    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
        <div class="mensagem-sucesso" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?></div>
        <?php unset($_SESSION['mensagem_sucesso']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['mensagem_erro'])): ?>
        <div class="mensagem-erro" style="display:block;"><?= htmlspecialchars($_SESSION['mensagem_erro']) ?></div>
        <?php unset($_SESSION['mensagem_erro']); ?>
    <?php endif; ?>

    <?php if ($semestre_vigente): ?>
    <div class="info-semestre" style="margin-bottom:1em; color:#555; font-size:0.95em;">
      Semestre letivo vigente: <b><?= $semestre_vigente->ano ?>/<?= $semestre_vigente->semestre ?></b> (<?= date('d/m/Y', strtotime($semestre_vigente->data_inicio)) ?> a <?= date('d/m/Y', strtotime($semestre_vigente->data_fim)) ?>)
    </div>
    <?php endif; ?>

    <!-- MUDANÇA: O action agora aponta para os scripts corretos -->
    <form action="<?= $modo_edicao ? 'processar_edicao_aluno.php' : 'processar_cadastro_aluno.php' ?>" method="POST" class="form-aluno">
        <?php if ($modo_edicao): ?>
            <input type="hidden" name="matricula" value="<?= htmlspecialchars($aluno->matricula) ?>">
        <?php endif; ?>

        <label>Matrícula
            <input type="text" name="matricula" required value="<?= $aluno->matricula ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>

        <label>Nome
            <input type="text" name="nome" required value="<?= $aluno->nome ?? '' ?>">
        </label>

        <label>Sobrenome
            <input type="text" name="sobrenome" required value="<?= $aluno->sobrenome ?? '' ?>">
        </label>

        <label>Email
            <input type="email" name="email" required value="<?= $aluno->email ?? '' ?>">
        </label>

        <label>CPF
            <input type="text" name="cpf" required maxlength="11" pattern="\d{11}" title="Digite os 11 números do CPF" value="<?= $aluno->cpf ?? '' ?>" <?= $modo_edicao ? 'readonly' : '' ?>>
        </label>

        <?php if (!$modo_edicao): ?>
            <label>Senha
                <input type="password" name="senha" required minlength="6">
            </label>
        <?php endif; ?>

        <label>Cargo
            <select name="cargo" required>
                <option value="Nenhum" <?= isset($aluno) && $aluno->cargo === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                <option value="Líder" <?= isset($aluno) && $aluno->cargo === 'Líder' ? 'selected' : '' ?>>Líder</option>
                <option value="Vice-líder" <?= isset($aluno) && $aluno->cargo === 'Vice-líder' ? 'selected' : '' ?>>Vice-líder</option>
            </select>
        </label>

        <label>Turma (Cota)
            <select name="cota_id" required>
                <option value="" disabled <?= !isset($aluno->cota_id) ? 'selected' : '' ?>>Selecione uma turma</option>
                <?php foreach ($cotas as $cota): ?>
                    <option value="<?= $cota->id ?>" <?= isset($aluno) && $aluno->cota_id == $cota->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cota->nome_completo) ?> (<?= htmlspecialchars($cota->sigla) ?>) - <?= htmlspecialchars($cota->periodo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Data Fim da Validade
            <input type="date" name="data_fim_validade" required 
                value="<?= $aluno->data_fim_validade ?? ($semestre_vigente->data_fim ?? '') ?>" 
                >
        </label>

        <?php if ($modo_edicao): ?>
            <!-- MUDANÇA: Estrutura do checkbox melhorada para usabilidade -->
            <label class="checkbox-label">
                <input type="checkbox" name="ativo" value="1" <?= isset($aluno) && $aluno->ativo ? 'checked' : '' ?>>
                Aluno Ativo
            </label>
        <?php endif; ?>

        <button type="submit"><?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Aluno' ?></button>
    </form>
    <a class="btn-back" href="dashboard_cad.php">Voltar</a>
</main>
<?php include_once '../../includes/footer.php'; ?>
