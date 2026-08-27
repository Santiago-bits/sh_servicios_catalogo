<?php
/**
 * ARCHIVO: app/views/admin/audit/index.php
 */
?>
<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" value="<?= e($filters['q'] ?? '') ?>"
                   placeholder="Descripción, usuario o IP">
        </div>
        <div>
            <label class="form-label" for="f-modulo">Módulo</label>
            <select class="form-select" id="f-modulo" name="modulo">
                <option value="">Todos</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= e($module) ?>" <?= ($filters['modulo'] ?? '') === $module ? 'selected' : '' ?>>
                        <?= e(ucfirst($module)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-accion">Acción</label>
            <select class="form-select" id="f-accion" name="accion">
                <option value="">Todas</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= e($action) ?>" <?= ($filters['accion'] ?? '') === $action ? 'selected' : '' ?>>
                        <?= e($actionLabels[$action] ?? ucfirst($action)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-user">Usuario</label>
            <select class="form-select" id="f-user" name="usuario">
                <option value="">Todos</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id'] ?>" <?= (string) ($filters['usuario'] ?? '') === (string) $user['id'] ? 'selected' : '' ?>>
                        <?= e($user['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-desde">Desde</label>
            <input type="date" class="form-control" id="f-desde" name="desde" value="<?= e($filters['desde'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" for="f-hasta">Hasta</label>
            <input type="date" class="form-control" id="f-hasta" name="hasta" value="<?= e($filters['hasta'] ?? '') ?>">
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('auditoria') ?>" class="btn btn-ghost">Limpiar</a>
        </div>

        <?php if (can('data.export')): ?>
            <div class="ms-auto">
                <a href="<?= admin_url('exportar/auditoria/xlsx') ?>" class="btn btn-ghost">
                    <i class="bi bi-file-earmark-excel"></i> Exportar
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-shield-check"></i> Registro de auditoría</h2>
        <span class="text-muted-2 small"><?= number_es($result['total']) ?> evento(s)</span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <div class="table-responsive-admin">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Descripción</th>
                        <th>IP</th>
                        <th>Datos</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $chipClass = match ($log['action']) {
                        'delete', 'access_denied', 'login_failed' => 'danger',
                        'create', 'login'                          => 'ok',
                        'price_change', 'price_bulk', 'stock_change', 'permissions_change' => 'warn',
                        default                                    => 'neutral',
                    };
                    ?>
                    <tr>
                        <td class="small">
                            <?= e(date_es((string) $log['created_at'], true)) ?>
                            <small class="d-block text-muted-2"><?= e(time_ago((string) $log['created_at'])) ?></small>
                        </td>
                        <td><?= e($log['user_name'] ?? 'Sistema') ?></td>
                        <td><span class="chip chip--<?= $chipClass ?>"><?= e($actionLabels[$log['action']] ?? $log['action']) ?></span></td>
                        <td class="text-muted-2 small"><?= e(ucfirst((string) $log['module'])) ?></td>
                        <td class="small"><?= e($log['description'] ?? '—') ?></td>
                        <td class="text-mono small text-muted-2"><?= e($log['ip'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($log['data'])): ?>
                                <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#logModal<?= (int) $log['id'] ?>">
                                    <i class="bi bi-braces"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted-2">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted-2">No hay eventos con esos filtros.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($result['last_page'] ?? 1) > 1): ?>
        <div class="card-admin__foot"><?php $view->partial('pagination', ['result' => $result]); ?></div>
    <?php endif; ?>
</div>

<?php foreach ($logs as $log): ?>
    <?php if (empty($log['data'])) { continue; } ?>
    <div class="modal fade" id="logModal<?= (int) $log['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Detalle del evento #<?= (int) $log['id'] ?></h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted-2 small mb-2">
                        <?= e(date_es((string) $log['created_at'], true)) ?> ·
                        <?= e($log['user_name'] ?? 'Sistema') ?> ·
                        <?= e($log['ip'] ?? '') ?>
                    </p>
                    <p><?= e($log['description'] ?? '') ?></p>

                    <?php $data = json_decode((string) $log['data'], true); ?>
                    <?php if (is_array($data)): ?>
                        <table class="table-admin">
                            <thead><tr><th>Campo</th><th>Antes</th><th>Ahora</th></tr></thead>
                            <tbody>
                            <?php foreach ($data as $field => $change): ?>
                                <tr>
                                    <td class="text-mono small"><?= e((string) $field) ?></td>
                                    <?php if (is_array($change) && array_key_exists('antes', $change)): ?>
                                        <td class="small text-muted-2"><?= e(str_limit((string) ($change['antes'] ?? ''), 60)) ?></td>
                                        <td class="small fw-bold"><?= e(str_limit((string) ($change['ahora'] ?? ''), 60)) ?></td>
                                    <?php else: ?>
                                        <td colspan="2" class="small"><?= e(is_scalar($change) ? (string) $change : json_encode($change, JSON_UNESCAPED_UNICODE)) ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
