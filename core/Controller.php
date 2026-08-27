<?php
/**
 * ARCHIVO: core/Controller.php
 * ---------------------------------------------------------------------
 * Controlador base: render de vistas, respuestas JSON, redirecciones,
 * verificación de permisos y validación.
 */

declare(strict_types=1);

namespace Core;

use App\Services\SettingService;

abstract class Controller
{
    protected string $layout = 'public';

    /** @param array<string,mixed> $data */
    protected function view(string $template, array $data = [], ?int $status = null): void
    {
        if ($status !== null) {
            http_response_code($status);
        }

        $data['settings'] = $data['settings'] ?? SettingService::all();
        $data['flash']    = Session::pullFlash();
        $data['errors']   = $data['errors'] ?? Session::errors();

        echo View::make($template, $data, $this->layout);

        Session::clearOld();
    }

    /** Renderiza una vista sin layout (útil para fragmentos AJAX). @param array<string,mixed> $data */
    protected function fragment(string $template, array $data = []): string
    {
        $data['settings'] = $data['settings'] ?? SettingService::all();
        return View::make($template, $data, '');
    }

    /** @param array<string,mixed> $data */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $url, int $status = 302): never
    {
        if (!str_starts_with($url, 'http')) {
            $url = BASE_URL . '/' . ltrim($url, '/');
        }
        header('Location: ' . $url, true, $status);
        exit;
    }

    protected function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        // Sólo se admite volver a URLs de este mismo sitio (anti open redirect)
        if (!str_starts_with($referer, BASE_URL)) {
            $referer = BASE_URL;
        }
        header('Location: ' . $referer, true, 302);
        exit;
    }

    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        if (Request::isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => $message ?: 'Error ' . $code], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $template = is_file(VIEW_PATH . '/errors/' . $code . '.php') ? 'errors/' . $code : 'errors/500';

        echo View::make($template, [
            'code'     => $code,
            'message'  => $message,
            'settings' => SettingService::all(),
            'flash'    => [],
        ], $this->layout);

        exit;
    }

    /** Corta con 403 si el usuario no tiene el permiso indicado. */
    protected function requirePermission(string $permission): void
    {
        if (!Auth::can($permission)) {
            $this->abort(403, 'No tenés permiso para realizar esta acción.');
        }
    }

    /**
     * Valida datos y, si falla, vuelve atrás con errores y valores previos.
     *
     * @param array<string,mixed>  $data
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     * @return array<string,mixed>
     */
    protected function validate(array $data, array $rules, array $labels = []): array
    {
        $validator = new Validator($data, $rules, $labels);

        if ($validator->fails()) {
            if (Request::isAjax()) {
                $this->json(['ok' => false, 'errors' => $validator->errors(), 'message' => 'Revisá los datos ingresados.'], 422);
            }

            Session::flashErrors($validator->errors());
            Session::flashInput($data);
            Session::flash('danger', 'Revisá los datos ingresados.');
            $this->back();
        }

        return $validator->validated();
    }

    protected function success(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function error(string $message): void
    {
        Session::flash('danger', $message);
    }
}
