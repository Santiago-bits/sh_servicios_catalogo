<?php
/**
 * ARCHIVO: app/views/admin/roles/edit.php
 */

$isAdminRole = $role['slug'] === 'admin';
?>
<form method="post" action="<?= admin_url('roles/' . (int) $role['id']) ?>">
    <?= csrf_field() ?>

    <div class="card-admin">
        <div class="card-admin__head">
            <h2><i class="bi bi-key-fill"></i> <?= e($role['name']) ?></h2>
            <a href="<?= admin_url('roles') ?>" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>

        <div class="card-admin__body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="r-name">Nombre *</label>
                    <input type="text" class="form-control" id="r-name" name="name" required maxlength="60" value="<?= e($role['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="r-desc">Descripción</label>
                    <input type="text" class="form-control" id="r-desc" name="description" maxlength="255" value="<?= e($role['description'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label class="filter-check mb-2">
                        <input type="checkbox" name="active" value="1" <?= (int) $role['active'] === 1 ? 'checked' : '' ?>
                            <?= (int) $role['is_system'] === 1 ? 'disabled' : '' ?>>
                        Activo
                    </label>
                </div>
            </div>

            <?php if ($isAdminRole): ?>
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                    <i class="bi bi-shield-fill-exclamation"></i>
                    El rol <strong>Administrador</strong> siempre conserva acceso total: sus permisos no se pueden recortar
                    desde acá para evitar que el sistema quede sin nadie que lo administre.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-admin">
        <div class="card-admin__head">
            <h2><i class="bi bi-list-check"></i> Permisos</h2>
            <?php if (!$isAdminRole): ?>
                <label class="filter-check m-0">
                    <input type="checkbox" data-check-all=".perm-check input[type=checkbox]"> Marcar todos
                </label>
            <?php endif; ?>
        </div>

        <div class="card-admin__body">
            <?php foreach ($permissions as $module => $items): ?>
                <div class="perm-module">
                    <div class="perm-module__head">
                        <strong><?= e($moduleNames[$module] ?? ucfirst($module)) ?></strong>
                        <span class="text-muted-2 small"><?= count($items) ?> permiso(s)</span>
                    </div>
                    <div class="perm-module__body">
                        <?php foreach ($items as $permission): ?>
                            <label class="perm-check">
                                <input type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>"
                                    <?= $isAdminRole || in_array((int) $permission['id'], $assigned, true) ? 'checked' : '' ?>
                                    <?= $isAdminRole ? 'disabled' : '' ?>>
                                <span>
                                    <?= e($permission['name']) ?>
                                    <code class="d-block text-muted-2" style="font-size:.7rem"><?= e($permission['slug']) ?></code>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-sticky-actions">
        <a href="<?= admin_url('roles') ?>" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Guardar permisos</button>
    </div>
</form>
