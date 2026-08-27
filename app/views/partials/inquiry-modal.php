<?php
/**
 * ARCHIVO: app/views/partials/inquiry-modal.php
 * Modal de consulta rápida sobre un producto (se envía por AJAX).
 *
 * @var array<string,mixed> $product
 */
?>
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:10px;overflow:hidden;border:0">

            <div class="modal-header" style="background:#111;color:#fff;border:0">
                <h2 class="modal-title h6 mb-0" id="inquiryModalTitle">
                    <i class="bi bi-envelope-fill text-accent"></i> Consultar por este producto
                </h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="quickInquiryForm" novalidate>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="producto_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="asunto" value="Consulta por <?= e($product['name']) ?>">

                    <div style="position:absolute;left:-9999px" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="alert alert-light border d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-box-seam text-accent"></i>
                        <div class="small">
                            <strong><?= e($product['name']) ?></strong><br>
                            <span class="text-muted-2">Código <?= e($product['code']) ?></span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="qi-nombre">Nombre y apellido *</label>
                            <input type="text" class="form-control" id="qi-nombre" name="nombre" required maxlength="160">
                            <span class="form-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="qi-email">Email *</label>
                            <input type="email" class="form-control" id="qi-email" name="email" required maxlength="160">
                            <span class="form-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="qi-telefono">Teléfono</label>
                            <input type="tel" class="form-control" id="qi-telefono" name="telefono" maxlength="40">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="qi-empresa">Empresa</label>
                            <input type="text" class="form-control" id="qi-empresa" name="empresa" maxlength="160">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="qi-mensaje">Mensaje *</label>
                            <textarea class="form-control" id="qi-mensaje" name="mensaje" rows="3" required maxlength="3000"
                                      placeholder="Quisiera recibir más información sobre este producto…"></textarea>
                            <span class="form-error"></span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid #eee">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-send-fill"></i> Enviar consulta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
