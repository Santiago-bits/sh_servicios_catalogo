<?php
/**
 * ARCHIVO: app/middleware/ApiThrottleMiddleware.php
 * ---------------------------------------------------------------------
 * Límite de peticiones por IP para el grupo /api. Evita el scraping
 * masivo del catálogo y la enumeración de /api/producto/{id}.
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\RateLimiter;
use Core\Request;

final class ApiThrottleMiddleware
{
    private const MAX    = 120;   // peticiones
    private const WINDOW = 60;    // segundos

    public function handle(?string $argument = null): void
    {
        $key = 'api:' . Request::ip();

        if (RateLimiter::tooMany($key, self::MAX, self::WINDOW)) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            header('Retry-After: ' . self::WINDOW);
            echo json_encode(
                ['ok' => false, 'message' => 'Demasiadas peticiones. Probá de nuevo en un minuto.'],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        RateLimiter::hit($key, self::WINDOW);
    }
}
