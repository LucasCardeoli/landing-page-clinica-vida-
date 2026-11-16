<?php
/**
 * agendamentos/cadastrar.php
 * GET: formulário vazio. POST: valida (regras de negócio) e grava.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conectar();
$erros = [];
$agendamento = ['medico_id' => 0, 'paciente_id' => 0, 'data_hora' => '', 'status' => 'agendado', 'observacao' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento = lerAgendamentoDoFormulario();
    $erros = validarAgendamento($agendamento);

    if (!$erros) {
        try {
            $sql = 'INSERT INTO agendamentos (medico_id, paciente_id, data_hora, status, observacao)
                    VALUES (:medico_id, :paciente_id, :data_hora, :status, :observacao)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':medico_id'   => $agendamento['medico_id'],
                ':paciente_id' => $agendamento['paciente_id'],
                ':data_hora'   => converterDataHora($agendamento['data_hora'])->format('Y-m-d H:i:s'),
                ':status'      => $agendamento['status'],
                ':observacao'  => $agendamento['observacao'] !== '' ? $agendamento['observacao'] : null,
            ]);

            definirMensagem('sucesso', 'Agendamento cadastrado com sucesso!');
            redirecionar('agendamentos/listar.php');
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar agendamento: ' . $e->getMessage());
            $erros[] = 'Não foi possível cadastrar o agendamento. Tente novamente.';
        }
    }
}

// Opções dos selects: só médicos ativos podem receber novos agendamentos
$medicos   = $pdo->query('SELECT id, nome, especialidade, ativo FROM medicos WHERE ativo = 1 ORDER BY nome')->fetchAll();
$pacientes = $pdo->query('SELECT id, nome, cpf FROM pacientes ORDER BY nome')->fetchAll();

$titulo = 'Novo agendamento';
$textoBotao = 'Agendar';
require __DIR__ . '/../includes/cabecalho.php';
exibirErros($erros);
require __DIR__ . '/_formulario.php';
require __DIR__ . '/../includes/rodape.php';
