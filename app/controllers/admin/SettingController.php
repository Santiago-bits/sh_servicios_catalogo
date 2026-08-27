<?php
/**
 * ARCHIVO: app/controllers/admin/SettingController.php
 * ---------------------------------------------------------------------
 * Configuración general: datos de la empresa, contacto, WhatsApp,
 * catálogo, monedas, cotizaciones, SEO y sistema.
 *
 * Nada de esto está hardcodeado en el código: todo vive acá.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Setting;
use App\Services\AuditService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use App\Services\SettingService;
use Core\Request;
use Core\Uploader;

class SettingController extends AdminController
{
    private const GROUP_LABELS = [
        'empresa'      => ['Empresa', 'bi-building'],
        'contacto'     => ['Contacto y redes', 'bi-telephone'],
        'catalogo'     => ['Catálogo', 'bi-grid'],
        'moneda'       => ['Monedas', 'bi-currency-dollar'],
        'cotizaciones' => ['Cotizaciones', 'bi-file-earmark-text'],
        'seo'          => ['SEO', 'bi-search'],
        'sistema'      => ['Sistema', 'bi-gear'],
    ];

    public function index(): void
    {
        $model = new Setting();

        $this->view('admin/settings/index', [
            'pageTitle'   => 'Configuración · Panel',
            'adminTitle'  => 'Configuración',
            'robots'      => 'noindex, nofollow',
            'groups'      => $model->grouped(),
            'groupLabels' => self::GROUP_LABELS,
            'currencies'  => $model->currencies(),
        ]);
    }

    public function update(): void
    {
        $model    = new Setting();
        $settings = $model->all();
        $changes  = [];

        foreach ($settings as $setting) {
            $key = (string) $setting['key_name'];

            // Las imágenes se manejan aparte (input file)
            if ($setting['type'] === 'image') {
                $file = Request::file('file_' . $key);
                if ($file !== null) {
                    $result = Uploader::image($file, 'settings', 800, false);
                    if ($result['ok']) {
                        Uploader::delete((string) ($setting['value'] ?? ''));
                        $model->put($key, $result['path']);
                        $changes[$key] = $result['path'];
                    } else {
                        $this->error($key . ': ' . $result['message']);
                    }
                }
                continue;
            }

            if ($setting['type'] === 'boolean') {
                $value = Request::bool($key) ? '1' : '0';
            } elseif (!isset($_POST[$key])) {
                continue;
            } else {
                $value = trim((string) $_POST[$key]);
            }

            // Validaciones puntuales
            if ($setting['type'] === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->error('El email de "' . $setting['label'] . '" no es válido.');
                continue;
            }
            if ($setting['type'] === 'number' && $value !== '' && !is_numeric(str_replace(',', '.', $value))) {
                $this->error('El valor de "' . $setting['label'] . '" debe ser numérico.');
                continue;
            }
            if ($key === 'contact_whatsapp') {
                $value = preg_replace('/\D+/', '', $value) ?? '';
            }
            if ($setting['type'] === 'url' && $value !== '' && !preg_match('#^https?://#i', $value)) {
                $value = 'https://' . $value;
            }

            $value = mb_substr($value, 0, 20000);

            if ((string) ($setting['value'] ?? '') !== $value) {
                $model->put($key, $value);
                $changes[$key] = $value;
            }
        }

        // Cotización del dólar → también actualiza la tabla currencies
        if (isset($changes['usd_rate'])) {
            $model->updateRate('USD', (float) str_replace(',', '.', $changes['usd_rate']));
        }

        SettingService::flush();
        CurrencyService::flush();

        if ($changes !== []) {
            AuditService::log('settings', 'settings', null, null, count($changes) . ' opción(es) modificada(s)', array_keys($changes));
            $this->success('Configuración guardada (' . count($changes) . ' cambio(s)).');
        } else {
            $this->success('No hubo cambios para guardar.');
        }

        $this->back();
    }

    /**
     * Trae la cotización del dólar ahora mismo desde lanacion.com.ar
     * (botón "Actualizar ahora" del panel de Monedas).
     */
    public function refreshDollar(): void
    {
        $result = ExchangeRateService::refreshNow();

        SettingService::flush();
        CurrencyService::flush();

        if ($result['ok']) {
            AuditService::log('settings', 'settings', null, null, 'Cotización del dólar actualizada desde lanacion.com.ar', ['usd_rate']);
            $this->success($result['message']);
        } else {
            $this->error('No se pudo actualizar la cotización: ' . $result['message']);
        }

        $this->back();
    }
}
