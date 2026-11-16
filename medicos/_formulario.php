<?php
/**
 * medicos/_formulario.php
 * Campos do formulário de médico, compartilhados por cadastrar.php e editar.php.
 * Espera as variáveis: $medico (array com os valores) e $textoBotao.
 * O "_" no nome indica que este arquivo não é aberto direto, só incluído.
 */
?>
<form method="post" enctype="multipart/form-data" class="formulario" novalidate>
    <div class="form-grupo">
        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome" maxlength="100" required
               value="<?= e($medico['nome']) ?>" placeholder="Ex.: Dra. Mariana Costa">
    </div>

    <div class="form-grupo">
        <label for="especialidade">Especialidade *</label>
        <input type="text" id="especialidade" name="especialidade" maxlength="100" required
               value="<?= e($medico['especialidade']) ?>" placeholder="Ex.: Cardiologia">
    </div>

    <div class="form-grupo">
        <label for="crm">CRM *</label>
        <input type="text" id="crm" name="crm" maxlength="20" required
               value="<?= e($medico['crm']) ?>" placeholder="CRM/SP 123456">
    </div>

    <div class="form-grupo">
        <label for="foto">Foto (JPG, PNG ou WEBP, até 2 MB)</label>
        <?php if (!empty($medico['foto'])): ?>
            <img class="miniatura miniatura-grande" src="<?= url(e($medico['foto'])) ?>" alt="Foto atual">
            <small>Envie outra imagem apenas se quiser trocar a foto atual.</small>
        <?php endif; ?>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="form-grupo form-check">
        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $medico['ativo'] ? 'checked' : '' ?>>
        <label for="ativo">Ativo (aparece no site para agendamento)</label>
    </div>

    <button type="submit" class="botao"><?= e($textoBotao) ?></button>
    <a href="<?= url('medicos/listar.php') ?>" class="botao botao-secundario">Cancelar</a>
</form>
