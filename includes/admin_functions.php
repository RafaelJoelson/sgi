<?php
function buscarUsuarios($conn, $termo, $tipo) {
    $termo = "%$termo%";
    $sql = "SELECT u.*, a.matricula, a.turma, a.is_lider, a.is_vice, a.ativo, 
                   s.siap, s.is_admin
            FROM Usuario u
            LEFT JOIN Aluno a ON u.id = a.id
            LEFT JOIN Servidor s ON u.id = s.id
            WHERE (u.nome LIKE ? OR u.cpf LIKE ? OR 
                  a.matricula LIKE ? OR s.siap LIKE ?)";
    
    if ($tipo !== 'todos') {
        $sql .= " AND u.tipo = ?";
        $params = [$termo, $termo, $termo, $termo, ($tipo === 'aluno' ? 'A' : 'S')];
    } else {
        $params = [$termo, $termo, $termo, $termo];
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarUsuario($conn, $dados) {
    try {
        $conn->beginTransaction();
        
        // Verifica se é aluno ou servidor
        $isAluno = isset($dados['matricula']);
        
        // Insere na tabela Usuario
        $sqlUsuario = "INSERT INTO Usuario (nome, email, senha, tipo, cpf)
                       VALUES (?, ?, ?, ?, ?)";
        
        $senha = password_hash($isAluno ? $dados['senha_aluno'] : $dados['senha_servidor'], PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare($sqlUsuario);
        $stmt->execute([
            $isAluno ? $dados['nome_aluno'] : $dados['nome_servidor'],
            $isAluno ? $dados['email_aluno'] : $dados['email_servidor'],
            $senha,
            $isAluno ? 'A' : 'S',
            $isAluno ? $dados['cpf_aluno'] : $dados['cpf_servidor']
        ]);
        
        $usuarioId = $conn->lastInsertId();
        
        // Insere na tabela específica
        if ($isAluno) {
            $sqlAluno = "INSERT INTO Aluno (id, matricula, turma, is_lider, is_vice)
                         VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlAluno);
            $stmt->execute([
                $usuarioId,
                $dados['matricula'],
                $dados['turma'],
                isset($dados['is_lider']) ? 1 : 0,
                isset($dados['is_vice']) ? 1 : 0
            ]);
        } else {
            $sqlServidor = "INSERT INTO Servidor (id, siap, is_admin)
                            VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sqlServidor);
            $stmt->execute([
                $usuarioId,
                $dados['siap'],
                isset($dados['is_admin']) ? 1 : 0
            ]);
        }
        
        $conn->commit();
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Usuário cadastrado com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao cadastrar usuário: ' . $e->getMessage()];
    }
}

function editarUsuario($conn, $dados) {
    try {
        $conn->beginTransaction();
        
        // Atualiza tabela Usuario
        $sqlUsuario = "UPDATE Usuario SET nome = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sqlUsuario);
        $stmt->execute([$dados['nome'], $dados['email'], $dados['id']]);
        
        // Atualiza tabela específica
        if ($dados['tipo'] === 'A') {
            $sqlAluno = "UPDATE Aluno SET matricula = ?, turma = ? WHERE id = ?";
            $stmt = $conn->prepare($sqlAluno);
            $stmt->execute([$dados['matricula'], $dados['turma'], $dados['id']]);
        } else {
            $sqlServidor = "UPDATE Servidor SET siap = ?, is_admin = ? WHERE id = ?";
            $stmt = $conn->prepare($sqlServidor);
            $stmt->execute([$dados['siap'], isset($dados['is_admin']) ? 1 : 0, $dados['id']]);
        }
        
        $conn->commit();
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Usuário atualizado com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao atualizar usuário: ' . $e->getMessage()];
    }
}

function excluirUsuario($conn, $id, $tipo) {
    try {
        $conn->beginTransaction();
        
        // Remove da tabela específica primeiro
        if ($tipo === 'A') {
            $stmt = $conn->prepare("DELETE FROM Aluno WHERE id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM Servidor WHERE id = ?");
        }
        $stmt->execute([$id]);
        
        // Remove da tabela Usuario
        $stmt = $conn->prepare("DELETE FROM Usuario WHERE id = ?");
        $stmt->execute([$id]);
        
        $conn->commit();
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Usuário excluído com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao excluir usuário: ' . $e->getMessage()];
    }
}

function gerenciarCotas($conn, $dados) {
    try {
        // Verifica se já existe cota para esta turma/semestre
        $sql = "SELECT id FROM CotaTurma WHERE turma = ? AND semestre = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$dados['turma'], $dados['semestre']]);
        
        if ($stmt->fetch()) {
            // Atualiza existente
            $sql = "UPDATE CotaTurma SET total_cotas = ?, data_validade = ? 
                    WHERE turma = ? AND semestre = ?";
        } else {
            // Cria nova
            $sql = "INSERT INTO CotaTurma (turma, semestre, total_cotas, data_validade)
                    VALUES (?, ?, ?, ?)";
        }
        
        $stmt = $conn->prepare($sql);
        $params = [
            $dados['total_cotas'],
            $dados['data_validade'],
            $dados['turma'],
            $dados['semestre']
        ];
        
        if ($stmt->execute($params)) {
            $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Cotas atualizadas com sucesso!'];
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao gerenciar cotas: ' . $e->getMessage()];
    }
}

function resetarSenha($conn, $id) {
    try {
        $novaSenha = password_hash($_POST['nova_senha'], PASSWORD_BCRYPT);
        
        $sql = "UPDATE Usuario SET senha = ?, precisa_trocar_senha = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$novaSenha, $id])) {
            $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Senha redefinida com sucesso!'];
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao redefinir senha: ' . $e->getMessage()];
    }
}

function formatarCPF($cpf) {
    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf);
}