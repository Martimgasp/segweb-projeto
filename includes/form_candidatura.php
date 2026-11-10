<?php
// Formulário partilhado (nova candidatura / edição).
// Espera: $d (valores), $acao (URL), $botao (texto), $totalDocs (opcional)
?>
<form method="post" action="<?= e($acao) ?>" enctype="multipart/form-data" class="form form-largo">
    <?= csrf_field() ?>
    <label>Instituição de ensino
        <input type="text" name="instituicao" value="<?= e($d['instituicao']) ?>" maxlength="150" required>
    </label>
    <label>Curso
        <input type="text" name="curso" value="<?= e($d['curso']) ?>" maxlength="150" required>
    </label>
    <label>Rendimento anual do agregado familiar (€)
        <input type="text" name="rendimento" value="<?= e($d['rendimento']) ?>" inputmode="decimal" maxlength="12" required>
    </label>
    <label>IBAN
        <input type="text" name="iban" value="<?= e($d['iban']) ?>" maxlength="34" required autocomplete="off">
        <span class="ajuda">Formato PT50 seguido de 21 dígitos.</span>
    </label>
    <label>Documentos comprovativos (matrícula, rendimentos, IBAN)
        <input type="file" name="documentos[]" multiple accept=".pdf,.jpg,.jpeg,.png">
        <span class="ajuda">PDF, JPG ou PNG, até <?= e(formatar_tamanho(UPLOAD_MAX_BYTES)) ?> cada.</span>
    </label>
    <button type="submit"><?= e($botao) ?></button>
</form>
