<?php
/**
 * ARCHIVO: app/views/admin/users/index.php
 */

use Core\Auth;
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-people-fill"></i> Usuarios</h2>
                <span class="text-muted-2 small"><?= count($users) ?> usuario(s)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Último acceso</th>
                                <th>Estado</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $locked = !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time(); ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <span class="admin-user__avatar" style="width:40px;height:40px">
                                            <?= e(mb_strtoupper(mb_substr((string) $user['name'], 0, 1))) ?>
                                        </span>
                                        <span>
                                            <span class="table-product__name">
                                                <?= e($user['name']) ?>
                                                <?php if ((int) $user['id'] === (int) Auth::id()): ?>
                                                    <span class="chip chip--accent ms-1">Vos</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="table-product__meta"><?= e($user['email']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="chip chip--<?= $user['role_slug'] === 'admin' ? 'accent' : 'neutral' ?>">
                                        <?= e($user['role_name']) ?>
                                    </span>
                                    <?php if (!empty($user['position'])): ?>
                                        <small class="d-block text-muted-2"><?= e($user['position']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e(date_es($user['last_login_at'] ?? null, true)) ?>
                                    <?php if (!empty($user['last_login_ip'])): ?>
                                        <small class="d-block text-muted-2 text-mono"><?= e($user['last_login_ip']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="chip chip--<?= (int) $user['active'] === 1 ? 'ok' : 'danger' ?>">
                                        <?= (int) $user['active'] === 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                    <?php if ($locked): ?>
                                        <span class="chip chip--warn d-block mt-1">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if ($canManage): ?>
                                        <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#userModal<?= (int) $user['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ((int) $user['id'] !== (int) Auth::id()): ?>
                                            <form method="post" action="<?= admin_url('usuarios/' . (int) $user['id'] . '/eliminar') ?>"
                                                  class="d-inline" data-confirm="¿Desactivar la cuenta de <?= e($user['name']) ?>?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-icon btn-icon--danger" title="Desactivar">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted-2 small">Sin permisos</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-key-fill"></i> Roles</h2>
                <?php if (can('roles.manage')): ?>
                    <a href="<?= admin_url('roles') ?>" class="btn btn-ghost btn-sm">Administrar roles</a>
                <?php endif; ?>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Descripción</th>
                            <th class="num">Usuarios</th>
                            <th class="num">Permisos</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><strong><?= e($role['name']) ?></strong></td>
                            <td class="text-muted-2 small"><?= e($role['description'] ?? '') ?></td>
                            <td class="num"><?= (int) $role['users_count'] ?></td>
                            <td class="num">
                                <?= $role['slug'] === 'admin' ? 'Todos' : (int) $role['permissions_count'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($canManage): ?>
        <div class="col-lg-4">
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-person-plus"></i> Nuevo usuario</h2></div>
                <div class="card-admin__body">
                    <form method="post" action="<?= admin_url('usuarios') ?>" class="row g-3">
                        <?= csrf_field() ?>

                        <div class="col-12">
                            <label class="form-label" for="u-name">Nombre y apellido *</label>
                            <input type="text" class="form-control" id="u-name" name="name" required maxlength="120">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="u-email">Email *</label>
                            <input type="email" class="form-control" id="u-email" name="email" required maxlength="160">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="u-pass">Contraseña *</label>
                            <input type="password" class="form-control" id="u-pass" name="password" required minlength="8"
                                   autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="u-pass2">Repetir *</label>
                            <input type="password" class="form-control" id="u-pass2" name="password_confirmation" required minlength="8"
                                   autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="u-role">Rol *</label>
                            <select class="form-select" id="u-role" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <?php if ($role['slug'] === 'cliente') { continue; } ?>
                                    <option value="<?= (int) $role['id'] ?>" <?= $role['slug'] === 'operario' ? 'selected' : '' ?>>
                                        <?= e($role['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="u-phone">Teléfono</label>
                            <input type="tel" class="form-control" id="u-phone" name="phone" maxlength="40">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="u-position">Puesto</label>
                            <input type="text" class="form-control" id="u-position" name="position" maxlength="120">
                        </div>
                        <div class="col-12">
                            <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Cuenta activa</label>
                            <label class="filter-check"><input type="checkbox" name="must_change_pw" value="1"> Debe cambiar la contraseña</label>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent w-100"><i class="bi bi-person-plus"></i> Crear usuario</button>
                            <p class="form-hint mt-2 mb-0">La contraseña debe tener al menos 8 caracteres con letras y números.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($canManage): ?>
    <?php foreach ($users as $user): ?>
        <div class="modal fade" id="userModal<?= (int) $user['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                    <div class="modal-header" style="background:#111;color:#fff;border:0">
                        <h2 class="modal-title h6 mb-0">Editar «<?= e($user['name']) ?>»</h2>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" action="<?= admin_url('usuarios/' . (int) $user['id']) ?>">
                        <div class="modal-body">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($user['name']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required maxlength="160" value="<?= e($user['email']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Rol *</label>
                                    <select class="form-select" name="role_id" required>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= (int) $role['id'] ?>" <?= (int) $user['role_id'] === (int) $role['id'] ? 'selected' : '' ?>>
                                                <?= e($role['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" name="phone" maxlength="40" value="<?= e($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Puesto</label>
                                    <input type="text" class="form-control" name="position" maxlength="120" value="<?= e($user['position'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password"
                                           placeholder="Dejar vacío para no cambiarla">
                                </div>
                                <div class="col-12">
                                    <label class="filter-check">
                                        <input type="checkbox" name="active" value="1" <?= (int) $user['active'] === 1 ? 'checked' : '' ?>> Cuenta activa
                                    </label>
                                    <label class="filter-check">
                                        <input type="checkbox" name="unlock" value="1"> Desbloquear intentos fallidos
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #eee">
                            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
