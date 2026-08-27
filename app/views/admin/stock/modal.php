<?php
/**
 * ARCHIVO: app/views/admin/stock/modal.php
 * Modal reutilizable para registrar movimientos de stock.
 */

use App\Services\StockService;
?>
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
            <div class="modal-header" style="background:#111;color:#fff;border:0">
                <h2 class="modal-title h6 mb-0" id="stockModalTitle">
                    <i class="bi bi-box-seam-fill text-accent"></i> Movimiento de stock
                </h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="post" action="<?= admin_url('stock/movimiento') ?>">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" id="stockProductId">

                    <div class="alert alert-light border d-flex justify-content-between align-items-center py-2">
                        <span><strong id="stockProductName">—</strong></span>
                        <span class="chip chip--neutral">Stock actual: <strong id="stockCurrent">0</strong></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="stockType">Tipo de movimiento</label>
                            <select class="form-select" id="stockType" name="type" required>
                                <?php foreach (StockService::TYPES as $value => $label): ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint">En un <strong>ajuste</strong>, la cantidad es el stock resultante.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="stockQuantity">Cantidad</label>
                            <input type="number" class="form-control" id="stockQuantity" name="quantity"
                                   min="0" step="1" value="1" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="stockReference">Referencia</label>
                            <input type="text" class="form-control" id="stockReference" name="reference"
                                   maxlength="80" placeholder="OC, remito, orden de trabajo…">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="stockReason">Motivo</label>
                            <input type="text" class="form-control" id="stockReason" name="reason"
                                   maxlength="255" placeholder="Ej: Compra a proveedor">
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid #eee">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Registrar movimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>
