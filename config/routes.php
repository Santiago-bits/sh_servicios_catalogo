<?php
/**
 * ARCHIVO: config/routes.php
 * ---------------------------------------------------------------------
 * Tabla de rutas del sistema.
 *
 *  · Sitio público      → App\Controllers\*
 *  · Panel interno      → App\Controllers\Admin\*   (prefijo /admin)
 *  · Endpoints AJAX     → prefijo /api
 *
 * Middlewares disponibles: auth, guest, permission:<slug>
 */

declare(strict_types=1);

use Core\Router;

/** @var Router $router */

// =====================================================================
// SITIO PÚBLICO
// =====================================================================
$router->get('/', 'HomeController@index');

// --- Maquinaria ------------------------------------------------------
$router->get('/maquinaria', 'MachineController@index');
$router->get('/maquinaria/{category}', 'MachineController@index');
$router->get('/maquinaria/{category}/{slug}', 'MachineController@show');

// --- Repuestos -------------------------------------------------------
$router->get('/repuestos', 'PartController@index');
$router->get('/repuestos/{category}', 'PartController@index');
$router->get('/repuestos/{category}/{slug}', 'PartController@show');

// --- Servicios / institucional --------------------------------------
$router->get('/servicios', 'ServiceController@index');
$router->get('/servicios/{slug}', 'ServiceController@show');
$router->get('/contacto', 'PageController@contact');
$router->post('/contacto', 'InquiryController@store');

// --- Buscador y herramientas ----------------------------------------
$router->get('/buscar', 'SearchController@index');
$router->get('/comparar', 'CompareController@index');
// Favoritos: función retirada (se quitó el botón "guardar en favoritos").

// --- Cotizador público ----------------------------------------------
$router->get('/cotizador', 'QuoteController@index');
$router->post('/cotizador', 'QuoteController@store');
$router->get('/cotizador/enviada/{number}', 'QuoteController@success');

// --- SEO -------------------------------------------------------------
$router->get('/sitemap.xml', 'SitemapController@index');

// =====================================================================
// API / AJAX PÚBLICA
// =====================================================================
$router->group('/api', static function (Router $router): void {
    $router->get('/buscar', 'SearchController@suggest');
    $router->get('/maquinaria', 'MachineController@ajaxIndex');
    $router->get('/repuestos', 'PartController@ajaxIndex');
    $router->get('/producto/{id:\d+}', 'ProductApiController@show');
    $router->get('/comparar', 'CompareController@data');
    $router->post('/consulta', 'InquiryController@quickStore');
    $router->post('/cotizador/agregar', 'QuoteController@addItem');
    $router->post('/cotizador/cantidad', 'QuoteController@updateItem');
    $router->post('/cotizador/quitar', 'QuoteController@removeItem');
    $router->post('/cotizador/vaciar', 'QuoteController@clear');
    $router->get('/cotizador', 'QuoteController@cart');
}, ['api-throttle']);

// =====================================================================
// PANEL ADMINISTRATIVO
// =====================================================================

// --- Autenticación ---------------------------------------------------
$router->get('/admin/login', 'Admin\AuthController@showLogin', ['guest']);
$router->post('/admin/login', 'Admin\AuthController@login', ['guest']);
$router->post('/admin/logout', 'Admin\AuthController@logout', ['auth']);
$router->get('/admin/perfil', 'Admin\AuthController@profile', ['auth']);
$router->post('/admin/perfil', 'Admin\AuthController@updateProfile', ['auth']);
$router->post('/admin/perfil/password', 'Admin\AuthController@updatePassword', ['auth']);

$router->group('/admin', static function (Router $router): void {

    // --- Dashboard ---------------------------------------------------
    $router->get('/', 'Admin\DashboardController@index', ['permission:dashboard.view']);
    $router->get('/novedades', 'Admin\DashboardController@news', ['permission:dashboard.view']);
    // Panel simplificado: alertas del sistema desactivadas.
    // $router->get('/alertas', 'Admin\DashboardController@alerts', ['permission:dashboard.view']);

    // --- Maquinaria --------------------------------------------------
    $router->get('/maquinaria', 'Admin\MachineController@index', ['permission:machines.view']);
    $router->get('/maquinaria/crear', 'Admin\MachineController@create', ['permission:machines.create']);
    $router->post('/maquinaria', 'Admin\MachineController@store', ['permission:machines.create']);
    $router->get('/maquinaria/{id:\d+}/editar', 'Admin\MachineController@edit', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}', 'Admin\MachineController@update', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/eliminar', 'Admin\MachineController@destroy', ['permission:machines.delete']);
    $router->post('/maquinaria/{id:\d+}/condicion', 'Admin\MachineController@toggleCondition', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/imagenes', 'Admin\MachineController@uploadImages', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/imagenes/{imageId:\d+}/principal', 'Admin\MachineController@setMainImage', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/imagenes/{imageId:\d+}/eliminar', 'Admin\MachineController@deleteImage', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/documentos', 'Admin\MachineController@uploadDocument', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/documentos/{docId:\d+}/eliminar', 'Admin\MachineController@deleteDocument', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/videos', 'Admin\MachineController@uploadVideo', ['permission:machines.edit']);
    $router->post('/maquinaria/{id:\d+}/videos/{videoId:\d+}/eliminar', 'Admin\MachineController@deleteVideo', ['permission:machines.edit']);

    // --- Repuestos ---------------------------------------------------
    $router->get('/repuestos', 'Admin\PartController@index', ['permission:parts.view']);
    $router->get('/repuestos/crear', 'Admin\PartController@create', ['permission:parts.create']);
    $router->post('/repuestos', 'Admin\PartController@store', ['permission:parts.create']);
    $router->get('/repuestos/{id:\d+}/editar', 'Admin\PartController@edit', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}', 'Admin\PartController@update', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/eliminar', 'Admin\PartController@destroy', ['permission:parts.delete']);
    $router->post('/repuestos/{id:\d+}/imagenes', 'Admin\PartController@uploadImages', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/imagenes/{imageId:\d+}/eliminar', 'Admin\PartController@deleteImage', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/compatibilidad', 'Admin\PartController@addCompatibility', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/compatibilidad/{compatId:\d+}/eliminar', 'Admin\PartController@deleteCompatibility', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/codigos', 'Admin\PartController@addCode', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/codigos/{codeId:\d+}/eliminar', 'Admin\PartController@deleteCode', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/videos', 'Admin\PartController@uploadVideo', ['permission:parts.edit']);
    $router->post('/repuestos/{id:\d+}/videos/{videoId:\d+}/eliminar', 'Admin\PartController@deleteVideo', ['permission:parts.edit']);

    // --- Taxonomías --------------------------------------------------
    $router->get('/categorias', 'Admin\CategoryController@index', ['permission:categories.manage']);
    $router->post('/categorias', 'Admin\CategoryController@store', ['permission:categories.manage']);
    $router->post('/categorias/{id:\d+}', 'Admin\CategoryController@update', ['permission:categories.manage']);
    $router->post('/categorias/{id:\d+}/eliminar', 'Admin\CategoryController@destroy', ['permission:categories.manage']);

    $router->get('/marcas', 'Admin\BrandController@index', ['permission:brands.manage']);
    $router->post('/marcas', 'Admin\BrandController@store', ['permission:brands.manage']);
    $router->post('/marcas/{id:\d+}', 'Admin\BrandController@update', ['permission:brands.manage']);
    $router->post('/marcas/{id:\d+}/eliminar', 'Admin\BrandController@destroy', ['permission:brands.manage']);

    // La ficha técnica de la máquina usa sólo los campos fijos del formulario:
    // la lista de "características" personalizadas y su ABM se retiraron.

    $router->get('/etiquetas', 'Admin\TagController@index', ['permission:tags.manage']);
    $router->post('/etiquetas', 'Admin\TagController@store', ['permission:tags.manage']);
    $router->post('/etiquetas/{id:\d+}', 'Admin\TagController@update', ['permission:tags.manage']);
    $router->post('/etiquetas/{id:\d+}/eliminar', 'Admin\TagController@destroy', ['permission:tags.manage']);

    $router->get('/servicios', 'Admin\ServiceController@index', ['permission:services.manage']);
    $router->post('/servicios', 'Admin\ServiceController@store', ['permission:services.manage']);
    $router->post('/servicios/{id:\d+}', 'Admin\ServiceController@update', ['permission:services.manage']);
    $router->post('/servicios/{id:\d+}/eliminar', 'Admin\ServiceController@destroy', ['permission:services.manage']);

    // =============================================================
    //  SECCIONES OCULTAS EN EL PANEL SIMPLIFICADO
    //  Los controladores siguen existiendo. Para reactivar una
    //  sección: descomentá su bloque acá y volvé a agregar su
    //  entrada en el menú (app/views/layouts/admin.php).
    // =============================================================
    /*
    // --- Precios -----------------------------------------------------
    $router->get('/precios', 'Admin\PriceController@index', ['permission:prices.view']);
    $router->post('/precios/{id:\d+}', 'Admin\PriceController@update', ['permission:prices.edit']);
    $router->post('/precios/masivo', 'Admin\PriceController@bulk', ['permission:prices.edit']);
    $router->get('/precios/{id:\d+}/historial', 'Admin\PriceController@history', ['permission:prices.history']);

    // --- Financiación ------------------------------------------------
    $router->get('/financiacion', 'Admin\FinancingController@index', ['permission:financing.manage']);
    $router->post('/financiacion', 'Admin\FinancingController@store', ['permission:financing.manage']);
    $router->post('/financiacion/{id:\d+}', 'Admin\FinancingController@update', ['permission:financing.manage']);
    $router->post('/financiacion/{id:\d+}/eliminar', 'Admin\FinancingController@destroy', ['permission:financing.manage']);
    $router->post('/financiacion/metodos', 'Admin\FinancingController@storeMethod', ['permission:financing.manage']);
    $router->post('/financiacion/metodos/{id:\d+}', 'Admin\FinancingController@updateMethod', ['permission:financing.manage']);

    // --- Cotizaciones ------------------------------------------------
    $router->get('/cotizaciones', 'Admin\QuoteController@index', ['permission:quotes.view']);
    $router->get('/cotizaciones/crear', 'Admin\QuoteController@create', ['permission:quotes.create']);
    $router->post('/cotizaciones', 'Admin\QuoteController@store', ['permission:quotes.create']);
    $router->get('/cotizaciones/{id:\d+}', 'Admin\QuoteController@show', ['permission:quotes.view']);
    $router->get('/cotizaciones/{id:\d+}/editar', 'Admin\QuoteController@edit', ['permission:quotes.edit']);
    $router->post('/cotizaciones/{id:\d+}', 'Admin\QuoteController@update', ['permission:quotes.edit']);
    $router->post('/cotizaciones/{id:\d+}/estado', 'Admin\QuoteController@changeStatus', ['permission:quotes.edit']);
    $router->post('/cotizaciones/{id:\d+}/eliminar', 'Admin\QuoteController@destroy', ['permission:quotes.delete']);
    $router->get('/cotizaciones/{id:\d+}/pdf', 'Admin\QuoteController@pdf', ['permission:quotes.view']);
    $router->post('/cotizaciones/{id:\d+}/email', 'Admin\QuoteController@sendEmail', ['permission:quotes.edit']);
    */

    // --- Consultas ---------------------------------------------------
    $router->get('/consultas', 'Admin\InquiryController@index', ['permission:inquiries.view']);
    $router->get('/consultas/{id:\d+}', 'Admin\InquiryController@show', ['permission:inquiries.view']);
    $router->post('/consultas/{id:\d+}', 'Admin\InquiryController@update', ['permission:inquiries.manage']);
    $router->post('/consultas/{id:\d+}/eliminar', 'Admin\InquiryController@destroy', ['permission:inquiries.manage']);

    // --- Usuarios y roles (reactivado: permite crear cuentas de menor
    //     privilegio y así ejercer de verdad el control por permisos) --
    $router->get('/usuarios', 'Admin\UserController@index', ['permission:users.view']);
    $router->post('/usuarios', 'Admin\UserController@store', ['permission:users.manage']);
    $router->post('/usuarios/{id:\d+}', 'Admin\UserController@update', ['permission:users.manage']);
    $router->post('/usuarios/{id:\d+}/eliminar', 'Admin\UserController@destroy', ['permission:users.manage']);
    $router->post('/usuarios/{id:\d+}/borrar', 'Admin\UserController@purge', ['permission:users.manage']);

    $router->get('/roles', 'Admin\RoleController@index', ['permission:roles.manage']);
    $router->get('/roles/{id:\d+}', 'Admin\RoleController@edit', ['permission:roles.manage']);
    $router->post('/roles', 'Admin\RoleController@store', ['permission:roles.manage']);
    $router->post('/roles/{id:\d+}', 'Admin\RoleController@update', ['permission:roles.manage']);
    $router->post('/roles/{id:\d+}/eliminar', 'Admin\RoleController@destroy', ['permission:roles.manage']);

    // --- Auditoría (reactivada: revisión de eventos de seguridad) ----
    $router->get('/auditoria', 'Admin\AuditController@index', ['permission:audit.view']);

    /*
    // --- Estadísticas (sigue oculta en el panel simplificado) -------
    $router->get('/estadisticas', 'Admin\StatsController@index', ['permission:stats.view']);
    $router->get('/estadisticas/datos', 'Admin\StatsController@data', ['permission:stats.view']);
    */

    // --- Configuración -----------------------------------------------
    $router->get('/configuracion', 'Admin\SettingController@index', ['permission:settings.manage']);
    $router->post('/configuracion', 'Admin\SettingController@update', ['permission:settings.manage']);
    $router->post('/configuracion/dolar', 'Admin\SettingController@refreshDollar', ['permission:settings.manage']);

    // --- Importación / exportación -----------------------------------
    $router->get('/importar', 'Admin\ImportController@index', ['permission:data.import']);
    $router->get('/importar/plantilla/{type}', 'Admin\ImportController@template', ['permission:data.import']);
    $router->post('/importar/previsualizar', 'Admin\ImportController@preview', ['permission:data.import']);
    $router->post('/importar/confirmar', 'Admin\ImportController@run', ['permission:data.import']);

    $router->get('/exportar', 'Admin\ExportController@index', ['permission:data.export']);
    $router->get('/exportar/{dataset}/{format}', 'Admin\ExportController@download', ['permission:data.export']);
    $router->get('/catalogo-pdf', 'Admin\ExportController@catalogForm', ['permission:data.export']);
    $router->post('/catalogo-pdf', 'Admin\ExportController@catalogPdf', ['permission:data.export']);

}, ['auth']);

// =====================================================================
// 404
// =====================================================================
$router->fallback('ErrorController@notFound');
