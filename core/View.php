<?php
/**
 * ARCHIVO: core/View.php
 * ---------------------------------------------------------------------
 * Motor de vistas: plantillas PHP planas con layout, secciones y
 * parciales. Las vistas NUNCA contienen SQL: sólo presentación.
 */

declare(strict_types=1);

namespace Core;

final class View
{
    /** @var array<string,mixed> */
    private static array $shared = [];

    /** @var array<string,string> */
    private array $sections = [];
    private array $stack = [];

    private string $layout = '';
    /** @var array<string,mixed> */
    private array $data = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /** @param array<string,mixed> $data */
    public static function make(string $template, array $data = [], string $layout = ''): string
    {
        $view = new self();
        return $view->render($template, $data, $layout);
    }

    /** @param array<string,mixed> $data */
    public function render(string $template, array $data = [], string $layout = ''): string
    {
        $this->data   = array_merge(self::$shared, $data);
        $this->layout = $layout;

        $content = $this->capture($template, $this->data);

        if ($this->layout !== '') {
            $this->sections['content'] = $content;
            $content = $this->capture('layouts/' . $this->layout, $this->data);
        }

        return $content;
    }

    /** @param array<string,mixed> $data */
    private function capture(string $template, array $data): string
    {
        // Los nombres de plantilla son siempre literales del código; este
        // control evita cualquier salto de carpeta si eso cambiara.
        if (!preg_match('#^[A-Za-z0-9_][A-Za-z0-9_/.\-]*$#', $template) || str_contains($template, '..')) {
            throw new \RuntimeException('Nombre de vista inválido: ' . $template);
        }

        $file = VIEW_PATH . '/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException('Vista no encontrada: ' . $template . ' (' . $file . ')');
        }

        $view = $this; // disponible dentro de la plantilla
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    // ----------------------------------------------------------------
    // API para las plantillas
    // ----------------------------------------------------------------

    public function start(string $section): void
    {
        $this->stack[] = $section;
        ob_start();
    }

    public function stop(): void
    {
        $section = array_pop($this->stack);
        if ($section === null) {
            return;
        }
        $this->sections[$section] = (string) ob_get_clean();
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return isset($this->sections[$name]) && trim($this->sections[$name]) !== '';
    }

    /** Incluye un parcial reutilizable. @param array<string,mixed> $data */
    public function partial(string $template, array $data = []): void
    {
        echo $this->capture('partials/' . $template, array_merge($this->data, $data));
    }

    /** Incluye cualquier vista por su ruta completa. @param array<string,mixed> $data */
    public function include(string $template, array $data = []): void
    {
        echo $this->capture($template, array_merge($this->data, $data));
    }
}
