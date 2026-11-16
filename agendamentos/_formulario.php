<?php
/**
 * agendamentos/_formulario.php
 * Campos do formulário de agendamento (usado por cadastrar.php e editar.php).
 * Espera: $agendamento, $medicos, $pacientes e $textoBotao.
 */
?>
<form method="post" class="formulario" novalidate>
    <div class="form-grupo">
        <label for="medico_id">Médico *</label>
        <select id="medico_id" name="medico_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($medicos as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= (int) $agendamento['medico_id'] === (int) $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['nome']) ?> — <?= e($m['especialidade']) ?><?= $m['ativo'] ? '' : ' (inativo)' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-grupo">
        <label for="paciente_id">Paciente *</label>
        <select id="paciente_id" name="paciente_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($pacientes as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) $agendamento['paciente_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['nome']) ?> — CPF <?= e(formatarCpf($p['cpf'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small>Paciente novo? <a href="<?= url('pacientes/cadastrar.php') ?>">Cadastre aqui</a> primeiro.</small>
    </div>

    <div class="form-grupo">
        <label for="data_hora">Data e hora *</label>
        <input type="datetime-local" id="data_hora" name="data_hora" required
               value="<?= e($agendamento['data_hora']) ?>">
    </div>

    <div class="form-grupo">
        <label for="status">Status *</label>
        <select id="status" name="status" required>
            <?php foreach (listaStatus() as $valor => $texto): ?>
                <option value="<?= e($valor) ?>" <?= $agendamento['status'] === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-grupo">
        <label for="observacao">Observação (opcional, até 500 caracteres)</label>
        <textarea id="observacao" name="observacao" rows="3" maxlength="500"><?= e($agendamento['observacao']) ?></textarea>
    </div>

    <button type="submit" class="botao"><?= e($textoBotao) ?></button>
    <a href="<?= url('agendamentos/listar.php') ?>" class="botao botao-secundario">Cancelar</a>
</form>
