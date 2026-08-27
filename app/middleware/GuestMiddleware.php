<?php
/**
 * ARCHIVO: app/middleware/GuestMiddleware.php
 * ---------------------------------------------------------------------
 * Impide que un usuario ya autenticado vuelva a la pantalla de login.
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;

final class GuestMiddleware
{
    public function handle(?string $argument = null): void
    {
        if (Auth::check()) {
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }
    }
}
