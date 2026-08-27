<?php
/**
 * ARCHIVO: app/views/partials/inquiry-form.php
 * Formulario de consulta reutilizable.
 *
 * @var int|null $productId
 * @var bool     $compact
 */

$productId = $productId ?? null;
$compact   = $compact ?? false;
$errors    = $errors ?? [];
?>
<form action="<?= url('contacto') ?>" method="post" class="row g-3" novalidate>
    <?= csrf_field() ?>

    <?php if ($productId !== null): ?>
        <input type="hidden" name="producto_id" value="<?= (int) $productId ?>">
    <?php endif; ?>

    <!-- Honeypot anti spam: invisible para las personas -->
    <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>No completar<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="if-nombre">Nombre y apellido *</label>
        <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
               id="if-nombre" name="nombre" required maxlength="160" value="<?= e(old('nombre')) ?>">
        <span class="form-error"><?= e($errors['nombre'] ?? '') ?></span>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="if-empresa">Empresa</label>
        <input type="text" class="form-control" id="if-empresa" name="empresa" maxlength="160" value="<?= e(old('empresa')) ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="if-email">Email *</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="if-email" name="email" required maxlength="160" value="<?= e(old('email')) ?>">
        <span class="form-error"><?= e($errors['email'] ?? '') ?></span>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="if-telefono">Teléfono</label>
        <input type="tel" class="form-control" id="if-telefono" name="telefono" maxlength="40" value="<?= e(old('telefono')) ?>">
    </div>

    <?php if (!$compact): ?>
        <div class="col-12">
            <label class="form-label" for="if-asunto">Asunto</label>
            <input type="text" class="form-control" id="if-asunto" name="asunto" maxlength="200" value="<?= e(old('asunto')) ?>">
        </div>
    <?php endif; ?>

    <div class="col-12">
        <label class="form-label" for="if-mensaje">Mensaje *</label>
        <textarea class="form-control <?= isset($errors['mensaje']) ? 'is-invalid' : '' ?>"
                  id="if-mensaje" name="mensaje" rows="<?= $compact ? 3 : 4 ?>" required
                  maxlength="3000" placeholder="Contanos qué necesitás: tipo de operación, capacidad, altura, cantidad…"><?= e(old('mensaje')) ?></textarea>
        <span class="form-error"><?= e($errors['mensaje'] ?? '') ?></span>
    </div>

    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-send-fill"></i> Enviar consulta
        </button>
        <span class="text-muted-2 small">Te respondemos dentro del horario de atención.</span>
    </div>
</form>
