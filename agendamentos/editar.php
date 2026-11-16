<?php
/**
 * agendamentos/editar.php?id=N
 * GET: formulário preenchido. POST: valida e atualiza (inclusive o status).
 */
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conectar();
$id = (int) ($_GET['id'] ?? 0);
$original = buscarAgendamento($id);

if (!$original) {
    definirMensagem('erro', 'Agendamento não encontrado.');
    redirecionar('agendamentos/listar.php');
}

$erros = [];
// Preenche o formulário com os dados do banco (data no formato do datetime-local)
$agendamento = [
    'medico_id'   => (int) $original['medico_id'],
    'paciente_id' => (int) $original['paciente_id'],
    'data_hora'   => date('Y-m-d\TH:i', strtotime($original['data_hora'])),
    'status'      => $original['status'],
    'observacao'  => $original['observacao'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento = lerAgendamentoDoFormulario();
    // Passa o original para a validação saber o que mudou
    $erros = validarAgendamento($agendamento, $original);

    if (!$erros) {
        try {
            $sql = 'UPDATE agendamentos
                    SET medico_id = :medico_id, paciente_id = :paciente_id, data_hora = :data_hora,
                        status = :status, observacao = :observacao
                    WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':medico_id'   => $agendamento['medico_id'],
                ':paciente_id' => $agendamento['paciente_id'],
                ':data_hora'   => converterDataHora($agendamento['data_hora'])->format('Y-m-d H:i:s'),
                ':status'      => $agendamento['status'],
                ':observacao'  => $agendamento['observacao'] !== '' ? $agendamento['observacao'] : null,
                ':id'          => $id,
            ]);

            definirMensagem('sucesso', 'Agendamento atualizado com sucesso!');
            redirecionar('agendamentos/listar.php');
        } catch (PDOException $e) {
            error_log('Erro ao editar agendamento ' . $id . ': ' . $e->getMessage());
            $erros[] = 'Não foi possível salvar as alterações. Tente novamente.';
        }
    }
}

// Médicos ativos + o médico atual deste agendamento (mesmo se inativo)
$stmt = $pdo->prepare('SELECT id, nome, especialidade, ativo FROM medicos
                       WHERE ativo = 1 OR id = :atual ORDER BY nome');
$stmt->execute([':atual' => $original['medico_id']]);
$medicos = $stmt->fetchAll();
$pacientes = $pdo->query('SELECT id, nome, cpf FROM pacientes ORDER BY nome')->fetchAll();

$titulo = 'Editar agendamento';
$textoBotao = 'Salvar alterações';
require __DIR__ . '/../includes/cabecalho.php';
exibirErros($erros);
require __DIR__ . '/_formulario.php';
require __DIR__ . '/../includes/rodape.php';
