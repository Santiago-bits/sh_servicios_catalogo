<?php
/**
 * ARCHIVO: app/views/admin/roles/index.php
 */
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-key-fill"></i> Roles</h2>
                <span class="text-muted-2 small"><?= count($roles) ?> rol(es)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Descripción</th>
                                <th class="num">Usuarios</th>
                                <th class="num">Permisos</th>
                                <th>Tipo</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e($role['name']) ?></span>
                                    <span class="table-product__meta text-mono"><?= e($role['slug']) ?></span>
                                </td>
                                <td class="text-muted-2 small"><?= e($role['description'] ?? '') ?></td>
                                <td class="num"><?= (int) $role['users_count'] ?></td>
                                <td class="num">
                                    <?php if ($role['slug'] === 'admin'): ?>
                                        <span class="chip chip--accent">Todos</span>
                                    <?php else: ?>
                                        <?= (int) $role['permissions_count'] ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $role['is_system'] === 1): ?>
                                        <span class="chip chip--neutral">Sistema</span>
                                    <?php else: ?>
                                        <span class="chip chip--info">Personalizado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="<?= admin_url('roles/' . (int) $role['id']) ?>" class="btn-icon" title="Editar permisos">
                                        <i class="bi bi-sliders"></i>
                                    </a>
                                    <?php if ((int) $role['is_system'] !== 1): ?>
                                        <form method="post" action="<?= admin_url('roles/' . (int) $role['id'] . '/eliminar') ?>"
                                              class="d-inline" data-confirm="¿Eliminar el rol «<?= e($role['name']) ?>»?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-icon btn-icon--danger"><i class="bi bi-trash"></i></button>
                                        </form>
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
            <div class="card-admin__head"><h2><i class="bi bi-list-check"></i> Permisos disponibles</h2></div>
            <div class="card-admin__body">
                <p class="form-hint">
                    Cada permiso se verifica en el servidor antes de ejecutar la acción, no sólo al mostrar el menú.
                </p>
                <?php foreach ($permissions as $module => $items): ?>
                    <div class="perm-module">
                        <div class="perm-module__head"><strong><?= e(ucfirst($module)) ?></strong></div>
                        <div class="perm-module__body">
                            <?php foreach ($items as $permission): ?>
                                <div class="perm-check">
                                    <i class="bi bi-dot"></i>
                                    <span>
                                        <?= e($permission['name']) ?>
                                        <code class="d-block text-muted-2" style="font-size:.72rem"><?= e($permission['slug']) ?></code>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nuevo rol</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('roles') ?>" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="r-name">Nombre *</label>
                        <input type="text" class="form-control" id="r-name" name="name" required maxlength="60"
                               placeholder="Ej: Encargado de depósito">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="r-desc">Descripción</label>
                        <input type="text" class="form-control" id="r-desc" name="description" maxlength="255">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Permisos iniciales</label>
                        <div class="picker" style="max-height:340px">
                            <?php foreach ($permissions as $module => $items): ?>
                                <div class="perm-module__head"><strong><?= e(ucfirst($module)) ?></strong></div>
                                <?php foreach ($items as $permission): ?>
                                    <label class="picker__item">
                                        <input type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>">
                                        <span class="flex-grow-1"><?= e($permission['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
