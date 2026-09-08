<?php
/**
 * ARCHIVO: app/controllers/admin/ImportController.php
 * ---------------------------------------------------------------------
 * Importación masiva en dos pasos: primero se previsualiza y valida,
 * recién después se confirma. Nada se escribe sin que el usuario vea
 * qué filas están bien y cuáles tienen error.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\ImportService;
use Core\Request;
use Core\Session;

class ImportController extends AdminController
{
    public function index(): void
    {
        // Previsualización pendiente de confirmar (la deja preview()).
        // Caduca a los 30 minutos, igual que en run().
        $preview = Session::get('_import_preview');
        if (is_array($preview) && (time() - (int) ($preview['created'] ?? 0)) > 1800) {
            Session::forget('_import_preview');
            $preview = null;
        }

        $this->view('admin/imports/index', [
            'pageTitle'      => 'Importar · Panel',
            'adminTitle'     => 'Importar',
            'robots'         => 'noindex, nofollow',
            'preview'        => is_array($preview) ? $preview : null,
            'machineColumns' => ImportService::MACHINE_COLUMNS,
            'partColumns'    => ImportService::PART_COLUMNS,
        ]);
    }

    /** Descarga de la plantilla CSV. */
    public function template(string $type): void
    {
        $type = $type === 'repuestos' ? 'spare_part' : 'machine';
        $name = $type === 'machine' ? 'plantilla-maquinaria' : 'plantilla-repuestos';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        header('Cache-Control: no-store');

        echo ImportService::template($type);
        exit;
    }

    public function preview(): void
    {
        $type = Request::post('tipo') === 'repuestos' ? 'spare_part' : 'machine';
        $file = Request::file('archivo');

        if ($file === null) {
            $this->error('Seleccioná un archivo CSV.');
            $this->redirect('admin/importar');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'txt'], true)) {
            $this->error('El archivo debe ser CSV. Si tenés un Excel, guardalo como "CSV (delimitado por punto y coma)".');
            $this->redirect('admin/importar');
        }

        if ((int) $file['size'] > 6 * 1024 * 1024) {
            $this->error('El archivo supera los 6 MB.');
            $this->redirect('admin/importar');
        }

        $temp = STORAGE_PATH . '/cache/import_' . bin2hex(random_bytes(8)) . '.csv';

        if (!is_dir(dirname($temp))) {
            @mkdir(dirname($temp), 0775, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $temp)) {
            $this->error('No se pudo procesar el archivo.');
            $this->redirect('admin/importar');
        }

        $result = ImportService::parse($temp, $type);

        if (!$result['ok']) {
            @unlink($temp);
            $this->error($result['message']);
            $this->redirect('admin/importar');
        }

        Session::set('_import_preview', [
            'type'    => $type,
            'file'    => $temp,
            'name'    => $file['name'],
            'valid'   => $result['valid'],
            'invalid' => $result['invalid'],
            'created' => time(),
        ]);

        $this->success($result['message']);
        $this->redirect('admin/importar');
    }

    public function run(): void
    {
        $preview = Session::get('_import_preview');

        if (!is_array($preview) || empty($preview['valid'])) {
            $this->error('No hay una previsualización válida. Subí el archivo nuevamente.');
            $this->redirect('admin/importar');
        }

        // La previsualización caduca a los 30 minutos
        if ((time() - (int) $preview['created']) > 1800) {
            Session::forget('_import_preview');
            $this->error('La previsualización caducó. Subí el archivo nuevamente.');
            $this->redirect('admin/importar');
        }

        $result = ImportService::run(
            $preview['valid'],
            (string) $preview['type'],
            Request::bool('actualizar', true)
        );

        @unlink((string) $preview['file']);
        Session::forget('_import_preview');

        $this->success(sprintf(
            'Importación finalizada: %d creado(s), %d actualizado(s).',
            $result['created'],
            $result['updated']
        ));

        foreach (array_slice($result['errors'], 0, 10) as $error) {
            $this->error($error);
        }

        $this->redirect($preview['type'] === 'machine' ? 'admin/maquinaria' : 'admin/repuestos');
    }
}
