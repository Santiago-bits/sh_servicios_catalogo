# SH SERVICIOS · Sistema de catálogo y gestión

Plataforma web para una empresa de **autoelevadores, maquinaria de carga, maquinaria
industrial, repuestos y servicios**.

Funciona como catálogo comercial público (maquinaria + repuestos) y, por detrás,
como sistema interno de gestión: precios con costo y ganancia, stock, cotizaciones
con PDF, financiación, estadísticas, auditoría, usuarios y permisos.

> **No hay venta online ni cobro desde la página.** La arquitectura y la base de
> datos ya están preparadas para incorporarla más adelante sin migrar nada
> (ver *Preparación para e-commerce* al final).

---

## 1. Requisitos

| Componente | Versión mínima | Notas |
|---|---|---|
| PHP | **8.1** (probado en 8.3 y 8.4) | ver tabla de extensiones abajo |
| MySQL / MariaDB | 5.7 / 10.4 | InnoDB y `utf8mb4` |
| Apache | 2.4 | con `mod_rewrite` habilitado |
| XAMPP | cualquiera reciente | trae todo lo anterior |

**No hace falta Composer ni ninguna librería externa.** Bootstrap, Bootstrap Icons y
Chart.js vienen incluidos en `assets/vendor/` (el sitio funciona sin internet),
y la generación de PDF y Excel se hace con código propio del proyecto (`lib/Pdf.php`
y `lib/Xlsx.php`).

### Extensiones de PHP

| Extensión | ¿Obligatoria? | Si falta |
|---|---|---|
| `pdo_mysql` | **Sí** | el sistema no arranca |
| `mbstring` | **Sí** | los acentos se rompen |
| `gd` | No | las imágenes se guardan sin reescalar ni miniatura, y los PDF salen sin fotos |
| `zip` | No | la exportación a Excel entrega CSV (Excel lo abre igual) |
| `fileinfo` | No | la validación de subidas usa la firma del archivo en lugar del MIME |
| `iconv` | No | los acentos de los PDF pueden verse raros |

> El sistema **detecta lo que falta y se adapta solo**: nunca tira un error por una
> extensión ausente. Para saber qué tenés en tu servidor, subí el proyecto y entrá a
> **`https://tudominio/compatibilidad.php`** — te lo dice todo en una pantalla.
> Después borrá ese archivo.

---

## 2. Instalación en XAMPP (paso a paso)

### 2.1 Copiar el proyecto

Descomprimí la carpeta dentro de `htdocs`:

```
C:\xampp\htdocs\sh-servicios\
```

### 2.2 Crear la base de datos

Desde **phpMyAdmin** (`http://localhost/phpmyadmin`):

1. Pestaña **Importar** → elegí `database/database.sql` → **Continuar**.
   Esto crea la base `sh_servicios`, las 38 tablas, los roles, permisos,
   categorías, características técnicas, servicios, métodos de pago y la
   configuración inicial.
2. *(Opcional pero recomendado para probar)* Importá también `database/seed.sql`:
   agrega 12 máquinas, 18 repuestos, marcas, compatibilidades, cotizaciones y
   consultas de ejemplo.

O por consola:

```bash
mysql -u root -p < database/database.sql
mysql -u root -p < database/seed.sql
```

### 2.3 Configurar el entorno

Copiá `.env.example` a `.env` y ajustá:

```ini
APP_ENV=local
APP_DEBUG=1
APP_URL=                       # vacío = detección automática
APP_KEY=poné-acá-una-cadena-larga-y-aleatoria
```

> **Importante:** cambiá `APP_KEY` por una cadena larga y aleatoria. Se usa para
> firmar la sesión y hashear las IP en las estadísticas.

**Conexión a la base de datos:** ya no se configura en `.env`, sino en
`coneccion.php` (raíz del proyecto). Ese archivo tiene **dos apartados**:

| Apartado | Cuándo se usa |
|----------|---------------|
| `$LOCALHOST` (XAMPP) | dominio `localhost` / `127.0.0.1`, o carpeta bajo `xampp` |
| `$INFINITYFREE` | cualquier otro dominio (sitio publicado) |

El apartado correcto se elige solo según el dominio; no hay que tocar nada al
subir. Para forzarlo a mano: variable de entorno `SH_ENV=local` o
`SH_ENV=infinityfree`. El repositorio trae `coneccion.example.php` como plantilla;
copialo a `coneccion.php` y completá el bloque de InfinityFree cuando publiques.

### 2.4 Permisos de escritura

El sistema necesita poder escribir en:

```
uploads/            (imágenes y documentos)
storage/logs/       (errores y registro de emails)
storage/cache/      (archivos temporales de importación)
storage/pdf/        (PDF temporales para adjuntar por email)
```

En Windows normalmente ya funciona. En Linux/Mac:

```bash
chmod -R 775 storage uploads
```

### 2.5 Abrir el sistema

```
Sitio público:  http://localhost/sh-servicios/
Panel interno:  http://localhost/sh-servicios/admin/login
```

El proyecto ya no usa la subcarpeta `/public`: la raíz del proyecto es también la
raíz web. El `.htaccess` de la raíz manda todo al front controller (`index.php`) y
bloquea el código fuente.

### 2.6 Usuario administrador

| Email | Contraseña | Rol |
|---|---|---|
| `admin@shservicios.com.ar` | `Admin2026!` | Administrador (acceso total) |

Con `seed.sql` se agregan además:

| Email | Contraseña | Rol |
|---|---|---|
| `operario@shservicios.com.ar` | `Operario2026!` | Operario |
| `vendedor@shservicios.com.ar` | `Vendedor2026!` | Vendedor (sin acceso a costos) |

> **Cambiá estas contraseñas apenas entres.** Panel → *Mi perfil* → *Cambiar contraseña*.
> Las contraseñas se guardan con `password_hash()`; nunca en texto plano.

### 2.7 Cargar los datos reales de la empresa

Todo lo que podría estar hardcodeado vive en la base de datos.
Andá a **Panel → Configuración** y completá:

- **Empresa:** nombre, razón social, CUIT, eslogan, descripción y logo.
- **Contacto:** teléfono, **número de WhatsApp** (sólo números, con código de país:
  `5491122334455`), emails, dirección, horarios, redes y mapa.
- **Monedas:** cotización del dólar (nunca se usa un valor fijo del código).
- **Cotizaciones:** prefijo, días de validez y condiciones comerciales por defecto.
- **SEO:** título, descripción y URL pública (se usa en `sitemap.xml` y en los PDF).

---

## 3. Probar sin Apache (opcional)

Si querés levantarlo rápido sin configurar Apache:

```bash
php -S localhost:8000 server.php
```

y abrí `http://localhost:8000`. El archivo `server.php` sólo sirve para esto;
en XAMPP no se usa.

---

## 3 bis. Subirlo a un hosting compartido (InfinityFree, Hostinger, cPanel…)

El sistema está preparado para funcionar con **todo el proyecto dentro de la carpeta
pública** del hosting, que es lo único que permiten los planes gratuitos.

### Pasos

1. **Subí todo por FTP** (FileZilla) dentro de `htdocs/` (en cPanel: `public_html/`).
   La estructura queda así:

   ```
   htdocs/
   ├── .htaccess      ← manda todo a index.php y bloquea el código fuente
   ├── index.php      ← front controller (raíz web)
   ├── coneccion.php  ← credenciales de la base (apartado InfinityFree)
   ├── assets/  uploads/  robots.txt
   ├── app/  core/  config/  lib/  database/  storage/
   └── .env
   ```

   El `.htaccess` de la raíz se encarga de que `app/`, `core/`, `config/`,
   `database/`, `storage/`, `lib/`, `.env`, `coneccion.php` y `server.php`
   devuelvan **403**. *(Verificado con Apache.)*

2. **Creá la base de datos** desde el panel de InfinityFree (*MySQL Databases*).
   Anotá los datos que te da, porque **no son los de XAMPP**:

   ```
   MySQL Host  = sqlXXX.infinityfree.com   (NO es "localhost")
   MySQL DB    = epiz_XXXXXXX_shservicios  (lleva un prefijo obligatorio)
   MySQL User  = epiz_XXXXXXX
   Password    = la de tu cuenta
   ```

3. **Importá el SQL** desde el phpMyAdmin del hosting: primero `database/database.sql`
   y, si querés datos de ejemplo, `database/seed.sql`.

   > Si el archivo es muy grande para el importador, subilo comprimido en `.zip`
   > o importá primero la estructura y después los `INSERT`.

4. **Cargá las credenciales en `coneccion.php`**, en el apartado `$INFINITYFREE`
   (con los datos del punto 2). El apartado `$LOCALHOST` dejalo como está: se usa
   solo cuando abrís el sitio en tu PC. Después creá el `.env` (copiá
   `.env.example`) con:

   ```ini
   APP_ENV=production
   APP_DEBUG=0
   APP_URL=https://tudominio.com     # sin barra final
   APP_KEY=una-cadena-larga-y-aleatoria
   SESSION_SECURE=1                  # si activaste el SSL gratuito
   ```

5. **Permisos**: poné `755` (o `777` si el hosting lo pide) en `uploads`,
   `storage/logs` y `storage/cache`.

6. **Verificá**: entrá a `https://tudominio.com/compatibilidad.php`. Si todo está en
   verde o amarillo, andá a `/admin/login`. **Después borrá `compatibilidad.php`.**

### Lo que NO vas a poder hacer en un hosting gratuito

| Limitación | Impacto real | Qué hacer |
|---|---|---|
| **`mail()` deshabilitada** y puerto SMTP (587) bloqueado | No salen los correos de consultas ni el envío de cotizaciones por email | Usá `MAIL_MAILER=api` con **Brevo** (300 correos/día gratis) o SendGrid: van por HTTPS, que sí está permitido. Cargá `MAIL_API_KEY` en el `.env` y verificá el remitente en esa cuenta. |
| **`gd` y `zip` pueden no estar** | Sin miniaturas, PDF sin fotos, Excel como CSV | Nada: el sistema se adapta solo. Si necesitás las fotos en los PDF, hace falta un hosting con GD. |
| **Límite de "hits" diarios y de procesos** | Si el sitio recibe mucho tráfico, el hosting lo suspende unas horas | Es la razón principal para pasar a un hosting pago cuando el sitio ande. |
| **Verificación anti-bot en las visitas** | Puede complicar la indexación en Google y romper llamadas desde afuera | Si el SEO importa, conviene un hosting pago con dominio propio. |

> **Recomendación honesta:** InfinityFree sirve muy bien para **mostrarle el sistema al
> cliente y probarlo**. Para el sitio definitivo de SH Servicios conviene un hosting
> pago barato (hay planes desde muy poco por mes) que tenga GD, envío de correo y sin
> límite de hits. El sistema funciona igual en los dos: no hay que cambiar código, sólo
> el `.env`.

Las consultas del formulario **siempre se guardan en la base de datos** y aparecen en
*Panel → Consultas*, funcione o no el correo. Y el botón de WhatsApp anda siempre,
porque no depende del servidor.

---

## 4. Estructura del proyecto

```
sh-servicios/
├── index.php               Front controller (raíz web; antes en public/)
├── .htaccess               Manda todo a index.php y bloquea el código fuente
├── coneccion.php           Credenciales de la base: apartados XAMPP e InfinityFree
├── coneccion.example.php   Plantilla de coneccion.php (sin credenciales reales)
├── .env.example            Plantilla de configuración
├── server.php              Router para "php -S" (sólo desarrollo)
├── robots.txt
├── assets/                 css, js, img y vendor (Bootstrap, Chart.js)
├── uploads/                Archivos subidos (con .htaccess que impide ejecutar PHP)
│
├── config/
│   ├── config.php          Bootstrap: constantes, autoload, errores, sesión
│   ├── database.php        Opciones PDO (lee las credenciales de coneccion.php)
│   └── routes.php          Tabla de rutas (público + API + panel)
│
├── core/                   Núcleo propio (sin framework)
│   ├── Router.php          Enrutador con parámetros y middlewares
│   ├── Database.php        PDO singleton + consultas preparadas
│   ├── Model.php           Modelo base: CRUD, paginación, filtros seguros
│   ├── Controller.php      Vistas, JSON, redirecciones, validación
│   ├── View.php            Motor de plantillas con layouts y parciales
│   ├── Auth.php            Login, permisos, bloqueo por intentos fallidos
│   ├── Csrf.php            Tokens anti-CSRF
│   ├── Validator.php       Validación del lado del servidor
│   ├── Request.php         Acceso saneado a la petición
│   ├── Session.php         Sesión endurecida y flash messages
│   ├── Uploader.php        Subida segura de archivos
│   └── Env.php             Lector de .env
│
├── app/
│   ├── controllers/        Sitio público
│   │   └── admin/          Panel interno
│   ├── models/             Acceso a datos (nada de SQL en las vistas)
│   ├── services/           Lógica de negocio
│   ├── middleware/         auth, guest, permission
│   ├── helpers/            Funciones globales (e(), money(), url()…)
│   └── views/
│       ├── layouts/        public, admin, auth
│       ├── partials/       navbar, footer, tarjeta de producto, paginación
│       └── admin/          Vistas del panel
│
├── lib/
│   ├── Pdf.php             Generador de PDF propio (sin dependencias)
│   └── Xlsx.php            Escritor de Excel .xlsx propio
│
├── database/
│   ├── database.sql        Esquema + datos base (obligatorio)
│   └── seed.sql            Datos de prueba (opcional)
│
└── storage/                logs, cache y pdf temporales
```

### Servicios de negocio (`app/services/`)

| Servicio | Responsabilidad |
|---|---|
| `PriceService` | Costo + ganancia = precio final, historial y ajustes masivos |
| `FinancingService` | Anticipo, saldo, interés, cuotas y plan de pagos |
| `QuoteService` | Carrito de cotización, totales y alta de cotizaciones |
| `PdfService` | PDF de cotización y catálogo |
| `WhatsAppService` | Enlaces `wa.me` con mensaje prellenado |
| `EmailService` | Envío por `mail()`, SMTP propio o registro en archivo |
| `ImageService` | Galería de productos, imagen principal, borrado físico |
| `StockService` | Movimientos con bloqueo de fila e historial |
| `AuditService` | Registro de auditoría |
| `SearchService` | Buscador inteligente (nombre, código, OEM, modelo) |
| `StatsService` | Métricas y series para los gráficos |
| `AlertService` | Alertas administrativas |
| `ImportService` / `ExportService` | Importación validada y exportación CSV/Excel |
| `SettingService` / `CurrencyService` | Configuración y multimoneda |

---

## 5. Cómo funciona lo importante

### 5.1 Precios (costo, ganancia y precio final)

```
Ganancia ($) = Costo × (Ganancia % ÷ 100)
Precio final = Costo + Ganancia ($)
```

Se calcula solo mientras escribís. Si cargás el **precio final**, el sistema deduce
la ganancia hacia atrás. Cada cambio queda registrado en `price_history` con el
valor anterior, el nuevo, el usuario, la fecha y el motivo.

**Seguridad del costo:** `cost_price`, `profit_percent` y `profit_amount` no salen
nunca del servidor hacia el sitio público. No se ocultan con CSS: las consultas
públicas directamente no seleccionan esas columnas (`Product::PUBLIC_COLUMNS`), y
`PriceService::publicView()` limpia cualquier respuesta JSON. Un usuario sin el
permiso `prices.view_cost` tampoco los recibe en el panel ni en las exportaciones.

### 5.2 Repuestos y compatibilidad

Un repuesto puede vincularse de dos formas:

1. **Con máquinas del catálogo** (`machine_spare_parts`): aparece en la ficha de la
   máquina como "Repuestos compatibles" y viceversa.
2. **Por marca + modelo** (`spare_part_compatibility`): sirve aunque esa máquina no
   esté publicada. **Esto es lo que hace que el cliente escriba `8FG25` y encuentre
   todo lo compatible.**

Además cada repuesto admite código interno, OEM, de fabricante y todos los
alternativos o equivalencias que quieras (`spare_part_codes`).

### 5.3 Stock

Todo cambio pasa por `StockService::move()`, que bloquea la fila del producto,
valida que haya stock suficiente y deja el movimiento registrado
(entrada, salida, reserva, liberación, ajuste, venta). El semáforo del catálogo:

🟢 disponible · 🟡 últimas unidades · 🔴 sin stock · ⚫ consultar disponibilidad

### 5.4 Características técnicas dinámicas

No hay una columna por característica. En **Panel → Características técnicas**
podés crear las que necesites (nombre, grupo, unidad, tipo de dato, si sirve como
filtro y si se muestra al público) y aparecen automáticamente en el formulario del
producto y en la ficha pública.

### 5.5 Cotizaciones y PDF

Numeración automática y sin repetir (`COT-000001`), estados
(borrador → enviada → aceptada / rechazada / vencida), ítems de catálogo o
manuales, descuentos, transporte, otros costos y financiación.

El PDF se genera con `lib/Pdf.php`, incluye logo, datos de la empresa, cliente,
detalle, plan de pagos, condiciones y contacto, y se puede **descargar**, **enviar
por email** (con el PDF adjunto) o **mandar por WhatsApp**.

### 5.6 Envío de correo

En `.env`, `MAIL_MAILER` acepta:

- `log` *(por defecto)*: guarda los correos en `storage/logs/emails.log`. Ideal para
  XAMPP, que normalmente no tiene servidor de correo.
- `mail`: usa la función `mail()` de PHP.
- `smtp`: cliente SMTP propio (completá `MAIL_HOST`, `MAIL_USERNAME`, etc.).

---

## 6. Roles y permisos

| Rol | Alcance |
|---|---|
| **Administrador** | Acceso total. Siempre conserva todos los permisos. |
| **Operario** | Productos, repuestos, precios, stock, consultas, cotizaciones, import/export. Sin usuarios, configuración ni auditoría. |
| **Vendedor** | Consulta el catálogo y trabaja cotizaciones y consultas. **No ve costos ni ganancias.** |
| **Cliente** | Reservado para las cuentas del futuro e-commerce. |

Los permisos se editan en **Panel → Roles** y se verifican **en el servidor** en cada
petición (middleware `permission:` + `Auth::can()`), no sólo ocultando botones.

---

## 7. Seguridad implementada

- **PDO con sentencias preparadas reales** (`ATTR_EMULATE_PREPARES = false`) en todas
  las consultas. Los nombres de columna y el orden se validan contra listas blancas.
- **XSS:** todo lo que se imprime pasa por `e()`. El HTML del editor se limpia con
  `clean_html()` (lista blanca de etiquetas, sin `on*` ni `javascript:`).
- **CSRF:** token por sesión verificado en cada POST/PUT/DELETE, también en AJAX.
- **Contraseñas:** `password_hash()` con costo 12 + `password_verify()`, rehash
  automático y bloqueo temporal tras varios intentos fallidos.
- **Sesiones:** cookie `HttpOnly` + `SameSite=Lax`, `use_strict_mode`, regeneración
  del ID al iniciar sesión y cada 15 minutos, expiración por inactividad y
  "fingerprint" del navegador.
- **Subida de archivos:** tipo MIME real (`finfo`), extensiones bloqueadas, límite de
  tamaño, nombre generado por el servidor y **las imágenes se regeneran con GD**
  (si traían código embebido, se pierde). `uploads/.htaccess` impide ejecutar
  PHP en esa carpeta.
- **Código fuente protegido:** el `.htaccess` de la raíz bloquea `config/`, `core/`,
  `app/`, `database/`, `storage/`, `lib/` y `.env`.
- **Cabeceras:** CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`
  y `Permissions-Policy`.
- **Errores:** con `APP_DEBUG=0` no se muestra nada al usuario; queda en
  `storage/logs/php-error.log`.

> Antes de publicar el sitio: `APP_ENV=production`, `APP_DEBUG=0`, `APP_KEY` propia,
> contraseñas cambiadas y, si hay HTTPS, `SESSION_SECURE=1`.

---

## 8. SEO

- URLs amigables: `/maquinaria/autoelevadores/toyota-8fg25` y
  `/repuestos/filtros/filtro-de-aceite-toyota`.
- Slug, `title`, `meta description`, Open Graph y canonical por producto.
- `sitemap.xml` generado dinámicamente y `robots.txt` en la raíz.

Cuando el sitio esté publicado, cargá la URL real en
**Configuración → SEO → URL pública del sitio**.

---

## 9. Importación masiva

**Panel → Importar.** Descargá la plantilla, completala en Excel y guardala como
**CSV (delimitado por punto y coma)**.

El sistema **primero previsualiza y valida**: muestra las filas correctas, las que
tienen error y el motivo de cada una. Recién después confirmás. Las marcas y
categorías que no existan se crean solas. En la columna `compatibilidad` podés poner
varias máquinas separadas por `|`, por ejemplo: `Toyota 8FG25|Hyster H2.5`.

---

## 10. Preparación para e-commerce futuro

Ya están creadas y relacionadas las tablas `customers`, `orders` y `order_items`, y
existe el rol *Cliente* y el interruptor **Configuración → Sistema → Habilitar venta
online**. Los favoritos ya se guardan en la tabla `favorites` con el token del
visitante, listos para asociarse a una cuenta.

Para activarlo más adelante alcanza con agregar los controladores de carrito, pago
(por ejemplo Mercado Pago) y facturación: **no hay que migrar la base ni rehacer el
catálogo**.

---

## 11. Problemas frecuentes

| Síntoma | Solución |
|---|---|
| "No se pudo conectar a la base de datos" | Revisá `DB_*` en `.env` y que MySQL esté iniciado en XAMPP. |
| Todas las URLs dan 404 salvo la portada | Falta `mod_rewrite`. En XAMPP: *Config → Apache (httpd.conf)* → descomentá `LoadModule rewrite_module` y en el `<Directory>` de `htdocs` poné `AllowOverride All`. Reiniciá Apache. |
| Las imágenes no se suben | Permisos de escritura en `uploads/` y `upload_max_filesize` / `post_max_size` en `php.ini`. |
| Los correos no llegan | Es lo esperado con `MAIL_MAILER=log`. Miralos en `storage/logs/emails.log` o configurá SMTP. |
| El logo no aparece en el PDF | Subilo en *Configuración → Empresa → Logo* (JPG o PNG). |
| Se ve el contenido pero sin estilos | Comprobá que exista `assets/` completo y que no esté bloqueado por el `.htaccess`. |

---

## 12. Pendientes recomendados antes de publicar

1. Cambiar las contraseñas de los usuarios de ejemplo (o eliminarlos).
2. Borrar los datos de prueba de `seed.sql` (productos, cotizaciones y consultas).
3. Completar los datos reales de la empresa en **Configuración**.
4. Cargar el logo y las imágenes de los productos.
5. Poner `APP_DEBUG=0` y `APP_ENV=production`.
6. Configurar el envío de correo por SMTP.
7. Hacer una copia de seguridad periódica de la base y de `uploads/`.
#   s h _ s e r v i c i o s _ c a t a l o g o  
 