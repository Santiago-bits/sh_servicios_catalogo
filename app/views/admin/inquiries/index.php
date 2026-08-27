<?php
/**
 * ARCHIVO: app/views/admin/inquiries/index.php
 */
?>
<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" value="<?= e($filters['q'] ?? '') ?>"
                   placeholder="Nombre, email, empresa">
        </div>
        <div>
            <label class="form-label" for="f-estado">Estado</label>
            <select class="form-select" id="f-estado" name="estado">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['estado'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e(inquiry_status_badge($status)['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-canal">Canal</label>
            <select class="form-select" id="f-canal" name="canal">
                <option value="">Todos</option>
                <?php foreach (['web' => 'Formulario web', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'telefono' => 'Teléfono'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['canal'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('consultas') ?>" class="btn btn-ghost">Limpiar</a>
        </div>

        <?php if (can('data.export')): ?>
            <div class="ms-auto">
                <a href="<?= admin_url('exportar/consultas/xlsx') ?>" class="btn btn-ghost">
                    <i class="bi bi-file-earmark-excel"></i> Exportar
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-chat-dots-fill"></i> Consultas</h2>
        <span class="text-muted-2 small"><?= number_es($result['total']) ?> consulta(s)</span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <div class="table-responsive-admin">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Mensaje</th>
                        <th>Canal</th>
                        <th>Estado</th>
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                    <?php $badge = inquiry_status_badge((string) $inquiry['status']); ?>
                    <tr style="<?= $inquiry['status'] === 'nueva' ? 'background:#FFFDF3' : '' ?>">
                        <td>
                            <?= e(date_es((string) $inquiry['created_at'])) ?>
                            <small class="d-block text-muted-2"><?= e(time_ago((string) $inquiry['created_at'])) ?></small>
                        </td>
                        <td>
                            <a href="<?= admin_url('consultas/' . (int) $inquiry['id']) ?>" class="table-product__name">
                                <?= e($inquiry['name']) ?>
                            </a>
                            <span class="table-product__meta"><?= e($inquiry['company'] ?: $inquiry['email']) ?></span>
                        </td>
                        <td class="small">
                            <?php if (!empty($inquiry['product_name'])): ?>
                                <span class="chip chip--neutral"><?= e(str_limit((string) $inquiry['product_name'], 28)) ?></span>
                            <?php else: ?>
                                <span class="text-muted-2">General</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted-2 small"><?= e(str_limit((string) $inquiry['message'], 60)) ?></td>
                        <td><span class="chip chip--neutral"><?= e(ucfirst((string) $inquiry['channel'])) ?></span></td>
                        <td><span class="chip chip--<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span></td>
                        <td class="actions">
                            <a href="<?= admin_url('consultas/' . (int) $inquiry['id']) ?>" class="btn-icon" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (!empty($inquiry['email'])): ?>
                                <a href="mailto:<?= e($inquiry['email']) ?>" class="btn-icon" title="Responder por email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (can('inquiries.manage')): ?>
                                <form method="post" action="<?= admin_url('consultas/' . (int) $inquiry['id'] . '/eliminar') ?>"
                                      class="d-inline" data-confirm="¿Eliminar la consulta de <?= e($inquiry['name']) ?>?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-icon btn-icon--danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted-2">
                            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                            No hay consultas con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($result['last_page'] ?? 1) > 1): ?>
        <div class="card-admin__foot"><?php $view->partial('pagination', ['result' => $result]); ?></div>
    <?php endif; ?>
</div>
