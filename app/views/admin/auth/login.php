<?php
/**
 * ARCHIVO: app/views/admin/auth/login.php
 */

use App\Services\SettingService;
?>
<div class="auth-card">
    <div class="auth-card__head">
        <?php $authLogo = (string) SettingService::get('company_logo', ''); ?>
        <?php if ($authLogo !== ''): ?>
            <img src="<?= e(upload_url($authLogo)) ?>" alt="<?= e(SettingService::companyName()) ?>">
        <?php else: ?>
            <span class="brand__mark"><i class="bi bi-truck-front-fill"></i></span>
            <h1><?= e(SettingService::companyName()) ?></h1>
        <?php endif; ?>
        <p>Panel de gestión interna</p>
    </div>

    <div class="auth-card__body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= admin_url('login') ?>" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label" for="login-email">Email</label>
                <div class="input-group-admin">
                    <input type="email" class="form-control" id="login-email" name="email"
                           required autofocus autocomplete="username"
                           value="<?= e(old('email')) ?>" placeholder="usuario@empresa.com">
                    <span class="input-group-admin__addon"><i class="bi bi-person"></i></span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="login-password">Contraseña</label>
                <div class="input-group-admin">
                    <input type="password" class="form-control" id="login-password" name="password"
                           required autocomplete="current-password" placeholder="••••••••">
                    <span class="input-group-admin__addon"><i class="bi bi-lock"></i></span>
                </div>
            </div>

            <button type="submit" class="btn btn-accent w-100 btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Ingresar
            </button>
        </form>

        <?php if (!empty($googleClientId)): ?>
            <div class="auth-divider"><span>o continuá con</span></div>

            <div id="g_id_onload"
                 data-client_id="<?= e($googleClientId) ?>"
                 data-callback="shGoogleCredentialResponse"
                 data-auto_prompt="false"></div>
            <div class="g_id_signin d-flex justify-content-center" data-type="standard" data-shape="rectangular"
                 data-theme="outline" data-text="signin_with" data-size="large" data-logo_alignment="center" data-width="300"></div>

            <script nonce="<?= csp_nonce() ?>">
                function shGoogleCredentialResponse(response) {
                    fetch(window.SHS.baseUrl + '/admin/login/google', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-Token': window.SHS.csrf
                        },
                        body: 'credential=' + encodeURIComponent(response.credential)
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.ok) {
                                window.location.href = data.redirect || (window.SHS.baseUrl + '/admin');
                            } else {
                                alert(data.message || 'No se pudo iniciar sesión con Google.');
                            }
                        })
                        .catch(function () { alert('Error de conexión. Probá de nuevo.'); });
                }
            </script>
            <script src="https://accounts.google.com/gsi/client" async defer></script>
        <?php endif; ?>
    </div>

    <div class="auth-card__foot">
        <a href="<?= url() ?>"><i class="bi bi-arrow-left"></i> Volver al sitio</a>
        <div class="mt-2" style="font-size:.76rem">
            Acceso restringido. Todos los intentos quedan registrados.
        </div>
    </div>
</div>
