<?php
/**
 * ARCHIVO: app/views/quotes/index.php
 * Cotizador público.
 */

use App\Services\PriceService;
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Cotización</span></nav>
        <h1>Solicitar cotización</h1>
        <p>Armá tu pedido con máquinas, repuestos y servicios. Te enviamos la cotización formal por email o WhatsApp.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">

            <!-- ============ ÍTEMS ============ -->
            <div class="col-lg-7">
                <div class="section-head mb-3">
                    <div>
                        <span class="eyebrow">Paso 1</span>
                        <h2 class="section-title" style="font-size:1.4rem">Tu pedido</h2>
                    </div>
                    <?php if (!empty($items)): ?>
                        <button type="button" class="btn btn-ghost btn-sm"
                                data-quote-clear="¿Vaciar la cotización?">
                            <i class="bi bi-trash"></i> Vaciar
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <i class="bi bi-cart-x"></i>
                        <h3>Tu cotización está vacía</h3>
                        <p>Agregá máquinas o repuestos desde el catálogo con el botón <strong>“Agregar a mi cotización”</strong>.</p>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <a href="<?= url('maquinaria') ?>" class="btn btn-accent">Ver maquinaria</a>
                            <a href="<?= url('repuestos') ?>" class="btn btn-outline-accent">Ver repuestos</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="quote-item">
                            <img class="quote-item__img"
                                 src="<?= e(product_image_url($item['product'])) ?>"
                                 alt="<?= e($item['description']) ?>">

                            <div class="quote-item__body">
                                <h3 class="quote-item__title">
                                    <a href="<?= e(product_url($item['product'])) ?>"><?= e($item['description']) ?></a>
                                </h3>
                                <div class="quote-item__meta">
                                    Código <?= e($item['code']) ?>
                                    <?php if (!empty($item['product']['brand_name'])): ?>
                                        · <?= e($item['product']['brand_name']) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="qty-stepper qty-stepper--sm mt-2" data-qty>
                                    <button type="button" data-qty-minus aria-label="Restar uno">−</button>
                                    <input type="text" inputmode="numeric" value="<?= number_es($item['quantity']) ?>"
                                           data-qty-input data-qline-input="<?= (int) $item['product_id'] ?>" aria-label="Cantidad">
                                    <button type="button" data-qty-plus aria-label="Sumar uno">+</button>
                                </div>
                            </div>

                            <div class="quote-item__price">
                                <?php if ($item['price_hidden']): ?>
                                    <span class="text-muted-2 small">A cotizar</span>
                                <?php else: ?>
                                    <span data-line-total><?= e(money($item['line_total'], (string) $item['product']['currency'])) ?></span>
                                <?php endif; ?>

                                <button type="button" class="btn btn-ghost btn-sm mt-2" data-quote-remove="<?= (int) $item['product_id'] ?>">
                                    <i class="bi bi-x-lg"></i> Quitar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-center mt-3">
                        <a href="<?= url('maquinaria') ?>" class="btn btn-ghost btn-sm">
                            <i class="bi bi-plus-lg"></i> Seguir agregando productos
                        </a>
                    </div>
                <?php endif; ?>

                <!-- ============ DATOS DEL CLIENTE ============ -->
                <div class="section-head mt-5 mb-3">
                    <div>
                        <span class="eyebrow">Paso 2</span>
                        <h2 class="section-title" style="font-size:1.4rem">Tus datos</h2>
                    </div>
                </div>

                <div class="contact-card">
                    <form method="post" action="<?= url('cotizador') ?>" class="row g-3" novalidate>
                        <?= csrf_field() ?>

                        <div style="position:absolute;left:-9999px" aria-hidden="true">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="q-nombre">Nombre y apellido *</label>
                            <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                                   id="q-nombre" name="nombre" required maxlength="160" value="<?= e(old('nombre')) ?>">
                            <span class="form-error"><?= e($errors['nombre'] ?? '') ?></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="q-empresa">Empresa</label>
                            <input type="text" class="form-control" id="q-empresa" name="empresa" maxlength="160" value="<?= e(old('empresa')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="q-email">Email *</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   id="q-email" name="email" required maxlength="160" value="<?= e(old('email')) ?>">
                            <span class="form-error"><?= e($errors['email'] ?? '') ?></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="q-telefono">Teléfono *</label>
                            <input type="tel" class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>"
                                   id="q-telefono" name="telefono" required maxlength="40" value="<?= e(old('telefono')) ?>">
                            <span class="form-error"><?= e($errors['telefono'] ?? '') ?></span>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">¿Necesitás algún servicio adicional?</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach (array_slice($services, 0, 8) as $service): ?>
                                    <label class="filter-check">
                                        <input type="checkbox" name="servicios[]" value="<?= e($service['title']) ?>">
                                        <?= e($service['title']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="q-mensaje">Observaciones</label>
                            <textarea class="form-control" id="q-mensaje" name="mensaje" rows="4" maxlength="2000"
                                      placeholder="Lugar de entrega, plazos, condiciones especiales…"><?= e(old('mensaje')) ?></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-accent btn-lg w-100" <?= empty($items) ? 'disabled' : '' ?>>
                                <i class="bi bi-send-fill"></i> Enviar solicitud de cotización
                            </button>
                            <?php if (empty($items)): ?>
                                <p class="form-hint text-center mt-2">Agregá al menos un producto para poder enviar.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============ RESUMEN ============ -->
            <div class="col-lg-5">
                <div class="summary-box">
                    <h2 class="h6 text-uppercase mb-3">Resumen</h2>

                    <div class="summary-row">
                        <span>Ítems</span>
                        <strong><?= count($items) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal estimado</span>
                        <strong id="sumSubtotal"><?= e(money($totals['subtotal'])) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Descuentos</span>
                        <strong>A definir</strong>
                    </div>
                    <div class="summary-row">
                        <span>Transporte</span>
                        <strong>A convenir</strong>
                    </div>

                    <div class="summary-total">
                        <span>Total estimado</span>
                        <strong id="sumTotal"><?= e(money($totals['total'])) ?></strong>
                    </div>

                    <p class="form-hint mt-3">
                        El total es orientativo: la cotización formal incluye descuentos y transporte.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
