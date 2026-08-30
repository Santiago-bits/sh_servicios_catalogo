<?php
/**
 * ARCHIVO: app/views/admin/inquiries/show.php
 */

$badge = inquiry_status_badge((string) $inquiry['status']);
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-chat-left-quote"></i> Consulta de <?= e($inquiry['name']) ?></h2>
                <span class="chip chip--<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
            </div>

            <div class="card-admin__body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <table class="table-admin">
                            <tbody>
                                <tr><td class="text-muted-2">Nombre</td><td class="fw-bold"><?= e($inquiry['name']) ?></td></tr>
                                <tr><td class="text-muted-2">Empresa</td><td><?= e($inquiry['company'] ?: '—') ?></td></tr>
                                <tr>
                                    <td class="text-muted-2">Email</td>
                                    <td>
                                        <?php if (!empty($inquiry['email'])): ?>
                                            <a href="mailto:<?= e($inquiry['email']) ?>"><?= e($inquiry['email']) ?></a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted-2">Teléfono</td>
                                    <td>
                                        <?php if (!empty($inquiry['phone'])): ?>
                                            <a href="tel:<?= e(preg_replace('/\s+/', '', (string) $inquiry['phone'])) ?>"><?= e($inquiry['phone']) ?></a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <table class="table-admin">
                            <tbody>
                                <tr><td class="text-muted-2">Recibida</td><td><?= e(date_es((string) $inquiry['created_at'], true)) ?></td></tr>
                                <tr><td class="text-muted-2">Canal</td><td><?= e(ucfirst((string) $inquiry['channel'])) ?></td></tr>
                                <tr><td class="text-muted-2">Asunto</td><td><?= e($inquiry['subject'] ?: '—') ?></td></tr>
                                <tr>
                                    <td class="text-muted-2">Producto</td>
                                    <td>
                                        <?php if (!empty($inquiry['product_name'])): ?>
                                            <a href="<?= admin_url(($inquiry['product_type'] === 'machine' ? 'maquinaria/' : 'repuestos/') . (int) $inquiry['product_id'] . '/editar') ?>">
                                                <?= e($inquiry['product_name']) ?>
                                            </a>
                                            <small class="d-block text-muted-2 text-mono"><?= e($inquiry['product_code']) ?></small>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (can('audit.view')): ?>
                                    <tr><td class="text-muted-2">IP</td><td class="text-mono small"><?= e($inquiry['ip'] ?? '—') ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3 class="form-section__title"><i class="bi bi-envelope-open"></i> Mensaje</h3>
                <div class="p-3" style="background:#FAFBFC;border-radius:6px;border-left:4px solid #F5C400">
                    <?= nl2br(e($inquiry['message'])) ?>
                </div>
            </div>

            <div class="card-admin__foot d-flex flex-wrap gap-2">
                <?php if (!empty($inquiry['email'])): ?>
                    <a href="mailto:<?= e($inquiry['email']) ?>?subject=<?= e(rawurlencode('Re: ' . ($inquiry['subject'] ?: 'Tu consulta'))) ?>"
                       class="btn btn-accent btn-sm">
                        <i class="bi bi-reply"></i> Responder por email
                    </a>
                <?php endif; ?>

                <?php if ($whatsappLink !== '#'): ?>
                    <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                        <i class="bi bi-whatsapp"></i> Responder por WhatsApp
                    </a>
                <?php endif; ?>


                <a href="<?= admin_url('consultas') ?>" class="btn btn-ghost btn-sm ms-auto">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if (can('inquiries.manage')): ?>
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-sliders"></i> Gestión</h2></div>
                <div class="card-admin__body">
                    <form method="post" action="<?= admin_url('consultas/' . (int) $inquiry['id']) ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="i-status">Estado</label>
                            <select class="form-select" id="i-status" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $inquiry['status'] === $status ? 'selected' : '' ?>>
                                        <?= e(inquiry_status_badge($status)['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="i-assigned">Asignada a</label>
                            <select class="form-select" id="i-assigned" name="assigned_to">
                                <option value="">Sin asignar</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= (int) $user['id'] ?>" <?= (int) $inquiry['assigned_to'] === (int) $user['id'] ? 'selected' : '' ?>>
                                        <?= e($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="i-note">Nota interna</label>
                            <textarea class="form-control" id="i-note" name="internal_note" rows="5"
                                      maxlength="4000" placeholder="No la ve el cliente"><?= e($inquiry['internal_note'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-check-lg"></i> Guardar
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($inquiry['replied_at'])): ?>
            <div class="card-admin">
                <div class="card-admin__body">
                    <p class="mb-0 text-muted-2 small">
                        <i class="bi bi-check-circle-fill" style="color:#2E9E5B"></i>
                        Marcada como respondida el <?= e(date_es((string) $inquiry['replied_at'], true)) ?>.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
