<?php
/**
 * ARCHIVO: app/views/partials/contact-details.php
 * Tarjeta "Datos de contacto" (inicio y página de contacto).
 *
 * Dos teléfonos y dos emails, sin distinguir área: salen de
 * Configuración → Contacto (Teléfono 1 / 2 y Email 1 / 2).
 *
 * @var string $headingTag  h2 | h3
 */

use App\Services\WhatsAppService;

$headingTag = in_array($headingTag ?? 'h3', ['h2', 'h3'], true) ? $headingTag : 'h3';

$iconDark = 'width:44px;height:44px;flex-shrink:0;display:grid;place-items:center;border-radius:10px;background:#1b1b1b;color:#F5C400';
$iconWa   = 'width:44px;height:44px;flex-shrink:0;display:grid;place-items:center;border-radius:10px;background:#25D366;color:#fff';
$grpLabel = 'font-size:.72rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#9a9a9a;margin:16px 0 2px';

$phones = [];
foreach (['contact_whatsapp', 'contact_whatsapp_parts'] as $key) {
    $digits = preg_replace('/\D+/', '', (string) setting($key, '')) ?? '';
    if ($digits !== '' && !in_array($digits, $phones, true)) {
        $phones[] = $digits;
    }
}

$emails = [];
foreach (['contact_email', 'contact_email_parts'] as $key) {
    $mail = trim((string) setting($key, ''));
    if ($mail !== '' && !in_array($mail, $emails, true)) {
        $emails[] = $mail;
    }
}
?>
<div class="contact-card h-100">
    <<?= $headingTag ?> class="h5 mb-1">Datos de contacto</<?= $headingTag ?>>

    <?php if ($phones !== []): ?>
        <p style="<?= $grpLabel ?>;margin-top:8px">Teléfonos · Atención 24 hs</p>
        <?php foreach ($phones as $i => $phone): ?>
            <div class="contact-info-item">
                <span style="<?= $iconWa ?>"><?= bs_icon('whatsapp') ?></span>
                <div>
                    <strong>Teléfono<?= count($phones) > 1 ? ' ' . ($i + 1) : '' ?></strong>
                    <a href="<?= e(WhatsAppService::link('', $phone)) ?>" target="_blank" rel="noopener">+<?= e($phone) ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($emails !== []): ?>
        <p style="<?= $grpLabel ?>">Emails</p>
        <?php foreach ($emails as $i => $mail): ?>
            <div class="contact-info-item">
                <span style="<?= $iconDark ?>"><?= bs_icon('envelope-fill') ?></span>
                <div>
                    <strong>Email<?= count($emails) > 1 ? ' ' . ($i + 1) : '' ?></strong>
                    <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (setting('contact_address') || setting('contact_hours')): ?>
        <p style="<?= $grpLabel ?>">Dónde y cuándo</p>
        <?php if (setting('contact_address')): ?>
            <div class="contact-info-item">
                <span style="<?= $iconDark ?>"><?= bs_icon('geo-alt-fill') ?></span>
                <div>
                    <strong>Dirección</strong>
                    <span><?= e(setting('contact_address')) ?><?= setting('contact_city') ? ', ' . e(setting('contact_city')) : '' ?></span>
                </div>
            </div>
        <?php endif; ?>
        <?php if (setting('contact_hours')): ?>
            <div class="contact-info-item">
                <span style="<?= $iconDark ?>"><?= bs_icon('clock-fill') ?></span>
                <div>
                    <strong>Horarios de oficina</strong>
                    <span><?= nl2br(e(setting('contact_hours'))) ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
