<?php
/**
 * medicos/excluir.php?id=N
 * GET: mostra a tela de confirmação. POST: exclui de fato.
 * Regra: médico com agendamentos não pode ser excluído.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$medico = buscarMedico($id);

if (!$medico) {
    definirMensagem('erro', 'Médico não encontrado.');
    redirecionar('medicos/listar.php');
}

// Quantos agendamentos este médico tem?
$stmt = conectar()->prepare('SELECT COUNT(*) FROM agendamentos WHERE medico_id = :id');
$stmt->execute([':id' => $id]);
$totalAgendamentos = (int) $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($totalAgendamentos > 0) {
        definirMensagem('erro', 'Não é possível excluir "' . $medico['nome'] . '": há '
            . $totalAgendamentos . ' agendamento(s). Exclua os agendamentos ou marque o médico como inativo.');
        redirecionar('medicos/listar.php');
    }

    try {
        $stmt = conectar()->prepare('DELETE FROM medicos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        // Apaga a foto do disco só se foi enviada pelo sistema (pasta src/medicos/)
        if ($medico['foto'] && str_starts_with($medico['foto'], 'src/medicos/')) {
            $arquivoFoto = __DIR__ . '/../' . $medico['foto'];
            if (is_file($arquivoFoto)) {
                unlink($arquivoFoto);
            }
        }

        definirMensagem('sucesso', 'Médico "' . $medico['nome'] . '" excluído com sucesso.');
    } catch (PDOException $e) {
        // Segurança extra: a FK (ON DELETE RESTRICT) também bloqueia no banco
        error_log('Erro ao excluir médico ' . $id . ': ' . $e->getMessage());
        definirMensagem('erro', 'Não foi possível excluir o médico. Verifique se ele possui agendamentos.');
    }
    redirecionar('medicos/listar.php');
}

$titulo = 'Excluir médico';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="formulario">
    <?php if ($totalAgendamentos > 0): ?>
        <div class="alerta alerta-erro">
            Não é possível excluir <strong><?= e($medico['nome']) ?></strong>: há
            <?= e($totalAgendamentos) ?> agendamento(s) vinculado(s). Para tirá-lo do site, edite e desmarque "Ativo".
        </div>
        <a href="<?= url('medicos/editar.php?id=' . $id) ?>" class="botao">Editar médico</a>
        <a href="<?= url('medicos/listar.php') ?>" class="botao botao-secundario">Voltar</a>
    <?php else: ?>
        <div class="alerta alerta-aviso">
            Tem certeza que deseja excluir o médico <strong><?= e($medico['nome']) ?></strong>
            (<?= e($medico['crm']) ?>)? Esta ação não pode ser desfeita.
        </div>
        <form method="post">
            <button type="submit" class="botao botao-perigo">Sim, excluir</button>
            <a href="<?= url('medicos/listar.php') ?>" class="botao botao-secundario">Cancelar</a>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/rodape.php'; ?>
