<?php
/**
 * ARCHIVO: app/controllers/admin/AuditController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Core\Request;

class AuditController extends AdminController
{
    public function index(): void
    {
        $filters = [
            'q'       => Request::get('q'),
            'modulo'  => Request::get('modulo'),
            'accion'  => Request::get('accion'),
            'usuario' => Request::get('usuario'),
            'desde'   => Request::get('desde'),
            'hasta'   => Request::get('hasta'),
        ];

        $model  = new ActivityLog();
        $result = $model->search($filters, max(1, Request::int('pagina', 1)), 40);

        $this->view('admin/audit/index', [
            'pageTitle'  => 'Auditoría · Panel',
            'adminTitle' => 'Auditoría',
            'robots'     => 'noindex, nofollow',
            'result'     => $result,
            'logs'       => $result['data'],
            'filters'    => $filters,
            'modules'    => $model->distinctModules(),
            'actions'    => $model->distinctActions(),
            'users'      => (new User())->allWithRole(),
            'moduleLabels' => [
                'auth'     => 'Acceso',
                'catalog'  => 'Catálogo',
                'categories' => 'Categorías',
                'brands'   => 'Marcas',
                'tags'     => 'Etiquetas',
                'data'     => 'Datos y exportaciones',
                'machines' => 'Maquinaria',
                'parts'    => 'Repuestos',
                'products' => 'Productos',
                'quotes'   => 'Cotizaciones',
                'services' => 'Servicios',
                'settings' => 'Configuración',
                'users'    => 'Usuarios',
                'roles'    => 'Roles y permisos',
                'prices'   => 'Precios',
                'financing' => 'Financiación',
                'inquiries' => 'Consultas',
            ],
            'actionLabels' => [
                'login'              => 'Inicio de sesión',
                'login_failed'       => 'Intento fallido',
                'logout'             => 'Cierre de sesión',
                'create'             => 'Alta',
                'update'             => 'Modificación',
                'delete'             => 'Baja',
                'price_change'       => 'Cambio de precio',
                'price_bulk'         => 'Ajuste masivo de precios',
                'permissions_change' => 'Cambio de permisos',
                'password_change'    => 'Cambio de contraseña',
                'access_denied'      => 'Acceso denegado',
                'import'             => 'Importación',
                'export'             => 'Exportación',
                'upload'             => 'Subida de archivo',
                'email'              => 'Envío de email',
                'settings'           => 'Configuración',
            ],
        ]);
    }
}
