<?php
/**
 * agendamentos/excluir.php?id=N
 * GET: tela de confirmação com os dados da consulta. POST: exclui.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$agendamento = buscarAgendamento($id);

if (!$agendamento) {
    definirMensagem('erro', 'Agendamento não encontrado.');
    redirecionar('agendamentos/listar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = conectar()->prepare('DELETE FROM agendamentos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        definirMensagem('sucesso', 'Agendamento de ' . $agendamento['paciente_nome'] . ' com '
            . $agendamento['medico_nome'] . ' excluído com sucesso.');
    } catch (PDOException $e) {
        error_log('Erro ao excluir agendamento ' . $id . ': ' . $e->getMessage());
        definirMensagem('erro', 'Não foi possível excluir o agendamento. Tente novamente.');
    }
    redirecionar('agendamentos/listar.php');
}

$titulo = 'Excluir agendamento';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="formulario">
    <div class="alerta alerta-aviso">
        Tem certeza que deseja excluir a consulta de <strong><?= e($agendamento['paciente_nome']) ?></strong>
        com <strong><?= e($agendamento['medico_nome']) ?></strong>
        em <strong><?= e(formatarDataHora($agendamento['data_hora'])) ?></strong>?
        Esta ação não pode ser desfeita.
        <br><small>Dica: para manter o histórico, você pode editar e mudar o status para "Cancelado".</small>
    </div>
    <form method="post">
        <button type="submit" class="botao botao-perigo">Sim, excluir</button>
        <a href="<?= url('agendamentos/listar.php') ?>" class="botao botao-secundario">Cancelar</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/rodape.php'; ?>
