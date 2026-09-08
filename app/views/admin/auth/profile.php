<?php
/**
 * ARCHIVO: app/views/admin/auth/profile.php
 */
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-person-gear"></i> Mis datos</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('perfil') ?>" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-md-6">
                        <label class="form-label" for="p-name">Nombre y apellido</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="p-name" name="name" required
                               maxlength="120" value="<?= e($user['name']) ?>">
                        <span class="form-error"><?= e($errors['name'] ?? '') ?></span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="p-email">Email</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="p-email" name="email" required
                               maxlength="160" value="<?= e($user['email']) ?>">
                        <span class="form-error"><?= e($errors['email'] ?? '') ?></span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="p-phone">Teléfono</label>
                        <input type="tel" class="form-control" id="p-phone" name="phone"
                               maxlength="40" value="<?= e($user['phone'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rol</label>
                        <input type="text" class="form-control" value="<?= e($user['role_name']) ?>" disabled>
                        <p class="form-hint">Sólo un administrador puede cambiar tu rol.</p>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="p-datos-current">Confirmá tu contraseña actual para guardar</label>
                        <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                               id="p-datos-current" name="current_password" required autocomplete="current-password">
                        <span class="form-error"><?= e($errors['current_password'] ?? '') ?></span>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-key"></i> Cambiar contraseña</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('perfil/password') ?>" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="p-current">Contraseña actual</label>
                        <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                               id="p-current" name="current_password" required autocomplete="current-password">
                        <span class="form-error"><?= e($errors['current_password'] ?? '') ?></span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="p-new">Contraseña nueva</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               id="p-new" name="password" required minlength="8" autocomplete="new-password">
                        <p class="form-hint">Mínimo 8 caracteres, combinando al menos 3 de: mayúsculas, minúsculas, números y símbolos. Evitá palabras obvias.</p>
                        <span class="form-error"><?= e($errors['password'] ?? '') ?></span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="p-confirm">Repetir contraseña</label>
                        <input type="password" class="form-control" id="p-confirm" name="password_confirmation"
                               required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-dark-2"><i class="bi bi-shield-lock"></i> Cambiar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-info-circle"></i> Sesión</h2></div>
            <div class="card-admin__body">
                <table class="table-admin">
                    <tbody>
                        <tr><td class="text-muted-2">Último acceso</td><td class="fw-bold"><?= e(date_es($user['last_login_at'] ?? null, true)) ?></td></tr>
                        <tr><td class="text-muted-2">IP del último acceso</td><td class="text-mono"><?= e($user['last_login_ip'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted-2">Cuenta creada</td><td><?= e(date_es($user['created_at'] ?? null)) ?></td></tr>
                        <tr><td class="text-muted-2">Estado</td>
                            <td><span class="chip chip--<?= (int) $user['active'] === 1 ? 'ok' : 'danger' ?>">
                                <?= (int) $user['active'] === 1 ? 'Activa' : 'Inactiva' ?>
                            </span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
