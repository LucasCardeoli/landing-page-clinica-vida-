<?php
/**
 * pacientes/cadastrar.php
 * GET: formulário vazio. POST: valida e grava o novo paciente.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$erros = [];
$paciente = ['nome' => '', 'cpf' => '', 'telefone' => '', 'email' => '', 'data_nascimento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente = lerPacienteDoFormulario();
    $erros = validarPaciente($paciente);

    if (!$erros) {
        try {
            $sql = 'INSERT INTO pacientes (nome, cpf, telefone, email, data_nascimento)
                    VALUES (:nome, :cpf, :telefone, :email, :data_nascimento)';
            $stmt = conectar()->prepare($sql);
            $stmt->execute([
                ':nome'            => $paciente['nome'],
                ':cpf'             => $paciente['cpf'],
                ':telefone'        => $paciente['telefone'],
                ':email'           => $paciente['email'],
                ':data_nascimento' => $paciente['data_nascimento'],
            ]);

            definirMensagem('sucesso', 'Paciente "' . $paciente['nome'] . '" cadastrado com sucesso!');
            redirecionar('pacientes/listar.php');
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar paciente: ' . $e->getMessage());
            $erros[] = 'Não foi possível cadastrar o paciente. Tente novamente.';
        }
    }
}

$titulo = 'Cadastrar paciente';
$textoBotao = 'Cadastrar';
require __DIR__ . '/../includes/cabecalho.php';
exibirErros($erros);
require __DIR__ . '/_formulario.php';
require __DIR__ . '/../includes/rodape.php';
