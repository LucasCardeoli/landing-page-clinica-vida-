<?php
/**
 * pacientes/_formulario.php
 * Campos do formulário de paciente (usado por cadastrar.php e editar.php).
 * Espera: $paciente (array com os valores) e $textoBotao.
 */
?>
<form method="post" class="formulario" novalidate>
    <div class="form-grupo">
        <label for="nome">Nome completo *</label>
        <input type="text" id="nome" name="nome" maxlength="100" required
               value="<?= e($paciente['nome']) ?>" placeholder="Ex.: Ana Beatriz Souza">
    </div>

    <div class="form-grupo">
        <label for="cpf">CPF *</label>
        <input type="text" id="cpf" name="cpf" maxlength="14" required inputmode="numeric"
               value="<?= e(formatarCpf($paciente['cpf'])) ?>" placeholder="000.000.000-00">
    </div>

    <div class="form-grupo">
        <label for="telefone">Telefone com DDD *</label>
        <input type="tel" id="telefone" name="telefone" maxlength="15" required
               value="<?= e(formatarTelefone($paciente['telefone'])) ?>" placeholder="(11) 98765-4321">
    </div>

    <div class="form-grupo">
        <label for="email">E-mail *</label>
        <input type="email" id="email" name="email" maxlength="150" required
               value="<?= e($paciente['email']) ?>" placeholder="nome@email.com">
    </div>

    <div class="form-grupo">
        <label for="data_nascimento">Data de nascimento *</label>
        <input type="date" id="data_nascimento" name="data_nascimento" required
               max="<?= date('Y-m-d') ?>" value="<?= e($paciente['data_nascimento']) ?>">
    </div>

    <button type="submit" class="botao"><?= e($textoBotao) ?></button>
    <a href="<?= url('pacientes/listar.php') ?>" class="botao botao-secundario">Cancelar</a>
</form>
