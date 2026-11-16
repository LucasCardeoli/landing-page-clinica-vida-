<?php
/**
 * pacientes/editar.php?id=N
 * GET: formulário preenchido. POST: valida e atualiza o paciente.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$paciente = buscarPaciente($id);

if (!$paciente) {
    definirMensagem('erro', 'Paciente não encontrado.');
    redirecionar('pacientes/listar.php');
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente = lerPacienteDoFormulario();
    // Passa o id para não acusar duplicado com o próprio CPF
    $erros = validarPaciente($paciente, $id);

    if (!$erros) {
        try {
            $sql = 'UPDATE pacientes
                    SET nome = :nome, cpf = :cpf, telefone = :telefone,
                        email = :email, data_nascimento = :data_nascimento
                    WHERE id = :id';
            $stmt = conectar()->prepare($sql);
            $stmt->execute([
                ':nome'            => $paciente['nome'],
                ':cpf'             => $paciente['cpf'],
                ':telefone'        => $paciente['telefone'],
                ':email'           => $paciente['email'],
                ':data_nascimento' => $paciente['data_nascimento'],
                ':id'              => $id,
            ]);

            definirMensagem('sucesso', 'Dados de "' . $paciente['nome'] . '" atualizados com sucesso!');
            redirecionar('pacientes/listar.php');
        } catch (PDOException $e) {
            error_log('Erro ao editar paciente ' . $id . ': ' . $e->getMessage());
            $erros[] = 'Não foi possível salvar as alterações. Tente novamente.';
        }
    }
}

$titulo = 'Editar paciente';
$textoBotao = 'Salvar alterações';
require __DIR__ . '/../includes/cabecalho.php';
exibirErros($erros);
require __DIR__ . '/_formulario.php';
require __DIR__ . '/../includes/rodape.php';
