<?php
/**
 * pacientes/excluir.php?id=N
 * GET: tela de confirmação. POST: exclui.
 * Regra: paciente com agendamentos não pode ser excluído.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$paciente = buscarPaciente($id);

if (!$paciente) {
    definirMensagem('erro', 'Paciente não encontrado.');
    redirecionar('pacientes/listar.php');
}

$stmt = conectar()->prepare('SELECT COUNT(*) FROM agendamentos WHERE paciente_id = :id');
$stmt->execute([':id' => $id]);
$totalAgendamentos = (int) $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($totalAgendamentos > 0) {
        definirMensagem('erro', 'Não é possível excluir "' . $paciente['nome'] . '": há '
            . $totalAgendamentos . ' agendamento(s) vinculado(s). Exclua os agendamentos primeiro.');
        redirecionar('pacientes/listar.php');
    }

    try {
        $stmt = conectar()->prepare('DELETE FROM pacientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        definirMensagem('sucesso', 'Paciente "' . $paciente['nome'] . '" excluído com sucesso.');
    } catch (PDOException $e) {
        // A FK (ON DELETE RESTRICT) também protege no banco
        error_log('Erro ao excluir paciente ' . $id . ': ' . $e->getMessage());
        definirMensagem('erro', 'Não foi possível excluir o paciente. Verifique se ele possui agendamentos.');
    }
    redirecionar('pacientes/listar.php');
}

$titulo = 'Excluir paciente';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="formulario">
    <?php if ($totalAgendamentos > 0): ?>
        <div class="alerta alerta-erro">
            Não é possível excluir <strong><?= e($paciente['nome']) ?></strong>: há
            <?= e($totalAgendamentos) ?> agendamento(s) vinculado(s). Exclua os agendamentos primeiro.
        </div>
        <a href="<?= url('pacientes/listar.php') ?>" class="botao botao-secundario">Voltar</a>
    <?php else: ?>
        <div class="alerta alerta-aviso">
            Tem certeza que deseja excluir o paciente <strong><?= e($paciente['nome']) ?></strong>
            (CPF <?= e(formatarCpf($paciente['cpf'])) ?>)? Esta ação não pode ser desfeita.
        </div>
        <form method="post">
            <button type="submit" class="botao botao-perigo">Sim, excluir</button>
            <a href="<?= url('pacientes/listar.php') ?>" class="botao botao-secundario">Cancelar</a>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/rodape.php'; ?>
