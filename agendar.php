<?php
/**
 * agendar.php
 * Recebe o formulário de agendamento do site (enviado pelo script.js via AJAX)
 * e responde em JSON: { sucesso: true/false, mensagem: "...", erros: [...] }
 *
 * Regras:
 * - valida paciente e agendamento no servidor
 * - se o CPF já existe, reutiliza o paciente; senão cria um novo
 * - médico não pode ter dois agendamentos ativos no mesmo horário
 */
require_once __DIR__ . '/includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

/** Envia a resposta JSON e encerra. */
function responder(bool $sucesso, string $mensagem, array $erros = []): void
{
    echo json_encode(['sucesso' => $sucesso, 'mensagem' => $mensagem, 'erros' => $erros]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responder(false, 'Requisição inválida.');
}

// 1) Lê os dados do formulário
$paciente = lerPacienteDoFormulario();
$agendamento = [
    'medico_id'   => (int) campo('medico_id'),
    'paciente_id' => null,            // ainda não sabemos: pode ser novo
    'data_hora'   => campo('data_hora'),
    'status'      => 'agendado',      // pelo site, sempre começa como "agendado"
    'observacao'  => campo('observacao'),
];

// 2) Valida tudo. CPF repetido aqui NÃO é erro (false no 3º parâmetro).
$erros = array_merge(
    validarPaciente($paciente, null, false),
    validarAgendamento($agendamento)
);

if ($erros) {
    responder(false, 'Não foi possível agendar. Verifique os dados.', $erros);
}

$dataHoraBanco = converterDataHora($agendamento['data_hora'])->format('Y-m-d H:i:s');
$pdo = conectar();

try {
    // 3) Transação: ou grava tudo (paciente + agendamento) ou nada
    $pdo->beginTransaction();

    // Trava a linha do médico até o fim da transação. Assim, se duas pessoas
    // tentarem o mesmo horário ao mesmo tempo, uma espera a outra terminar.
    $stmt = $pdo->prepare('SELECT nome FROM medicos WHERE id = :id AND ativo = 1 FOR UPDATE');
    $stmt->execute([':id' => $agendamento['medico_id']]);
    $nomeMedico = $stmt->fetchColumn();

    // Confere o horário de novo, agora com o médico travado
    if (!$nomeMedico || horarioOcupado($agendamento['medico_id'], $dataHoraBanco)) {
        $pdo->rollBack();
        responder(false, 'Este horário acabou de ser ocupado. Escolha outro horário.');
    }

    // 4) Paciente: reutiliza pelo CPF ou cria
    $stmt = $pdo->prepare('SELECT id FROM pacientes WHERE cpf = :cpf');
    $stmt->execute([':cpf' => $paciente['cpf']]);
    $pacienteId = $stmt->fetchColumn();
    $pacienteNovo = !$pacienteId;

    if ($pacienteNovo) {
        $stmt = $pdo->prepare('INSERT INTO pacientes (nome, cpf, telefone, email, data_nascimento)
                               VALUES (:nome, :cpf, :telefone, :email, :data_nascimento)');
        $stmt->execute([
            ':nome'            => $paciente['nome'],
            ':cpf'             => $paciente['cpf'],
            ':telefone'        => $paciente['telefone'],
            ':email'           => $paciente['email'],
            ':data_nascimento' => $paciente['data_nascimento'],
        ]);
        $pacienteId = $pdo->lastInsertId();
    }
    // Obs.: paciente já cadastrado NÃO tem os dados sobrescritos pelo site,
    // para ninguém alterar o cadastro de outra pessoa só sabendo o CPF.

    // 5) Grava o agendamento
    $stmt = $pdo->prepare('INSERT INTO agendamentos (medico_id, paciente_id, data_hora, status, observacao)
                           VALUES (:medico_id, :paciente_id, :data_hora, :status, :observacao)');
    $stmt->execute([
        ':medico_id'   => $agendamento['medico_id'],
        ':paciente_id' => $pacienteId,
        ':data_hora'   => $dataHoraBanco,
        ':status'      => 'agendado',
        ':observacao'  => $agendamento['observacao'] !== '' ? $agendamento['observacao'] : null,
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro no agendamento pelo site: ' . $e->getMessage());
    responder(false, 'Não foi possível concluir o agendamento agora. Tente novamente em instantes.');
}

$mensagem = 'Consulta agendada com sucesso com ' . $nomeMedico . ' em '
    . date('d/m/Y \à\s H:i', strtotime($dataHoraBanco)) . '.';
if (!$pacienteNovo) {
    $mensagem .= ' Encontramos seu cadastro pelo CPF e usamos os dados já registrados.';
}
$mensagem .= ' Entraremos em contato para confirmar.';

responder(true, $mensagem);
