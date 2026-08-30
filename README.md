# SH Servicios · Catálogo web + panel

Sitio web para una empresa de **autoelevadores, maquinaria y repuestos**.

- **Sitio público:** catálogo de maquinaria y repuestos, servicios, y formulario de contacto / pedido de cotización.
- **Panel interno:** carga y edición del catálogo, más la configuración básica de la empresa.

Hecho en **PHP puro (sin framework y sin Composer)**. Corre en XAMPP o en cualquier hosting con PHP 8 + MySQL.

---

## 1. Requisitos

| Componente | Mínimo |
|---|---|
| PHP | 8.1 o superior (con `pdo_mysql` y `mbstring`) |
| MySQL / MariaDB | 5.7 / 10.4 |
| Apache | 2.4 con `mod_rewrite` |

XAMPP ya trae todo. No hay que instalar librerías: Bootstrap y los iconos vienen incluidos en `assets/vendor/`.

---

## 2. Instalación en XAMPP

1. **Copiar el proyecto** a `C:\xampp\htdocs\sh-servicios\`.

2. **Crear la base de datos.** En `http://localhost/phpmyadmin` → creá la base `sh_servicios` y usala.

   > El script de instalación (`database.sql`, con tablas + configuración inicial) y los
   > datos de ejemplo (`seed.sql`) **no vienen en esta copia**. Están en el historial de Git:
   > `git checkout 48fb676 -- database/` los restaura en la carpeta `database/`.
   > Después importalos desde phpMyAdmin (pestaña **Importar**) o por consola:
   > `mysql -u root sh_servicios < database/database.sql`

3. **Configurar la conexión.** Las credenciales de la base van en `coneccion.php` (en la raíz).
   Copiá `coneccion.example.php` a `coneccion.php`. Trae dos bloques:

   | Bloque | Cuándo se usa |
   |---|---|
   | `$LOCALHOST` | cuando abrís el sitio en tu PC (XAMPP) — ya viene listo para `root` sin contraseña |
   | `$INFINITYFREE` | cuando el sitio está publicado en un hosting |

   El bloque correcto se elige solo según el dominio. No hay que tocar nada más para probar en local.

4. **Configurar el entorno.** Copiá `.env.example` a `.env`. Para desarrollo alcanza con:
   ```ini
   APP_ENV=local
   APP_DEBUG=1
   APP_URL=
   ```

5. **Abrir el sistema:**
   ```
   Sitio:  http://localhost/sh-servicios/
   Panel:  http://localhost/sh-servicios/admin/login
   ```

---

## 3. Entrar al panel

| Email | Contraseña |
|---|---|
| `admin@shservicios.com.ar` | `Admin2026!` |

Cambiá la contraseña apenas entres: **avatar arriba a la derecha → Mi perfil → Cambiar contraseña**.

Hay un solo usuario administrador. No hay gestión de usuarios ni roles.

---

## 4. Qué se hace desde el panel

| Sección | Para qué |
|---|---|
| **Inicio** | Resumen: cantidad de productos, repuestos, consultas, y qué falta completar (sin precio, sin imagen, sin categoría). |
| **Maquinaria** / **Repuestos** | Alta, edición y baja de productos. |
| **Categorías** / **Marcas** / **Características** / **Etiquetas** | Cómo se organiza y filtra el catálogo. |
| **Servicios** | Los servicios que muestra el sitio. |
| **Consultas** | Bandeja de entrada de los formularios de contacto y pedidos de cotización. |
| **Importar** / **Exportar** | (En desarrollo.) |
| **Configuración** | Datos de la empresa, contacto y redes, cotización del dólar. |

### Configuración inicial de la empresa

Andá a **Panel → Configuración** y completá:

- **Empresa:** nombre, eslogan, descripción, logo, imagen del inicio.
- **Contacto y redes:** email y teléfono de ventas, email y teléfono de repuestos, dirección, horarios, redes sociales.
  El teléfono va **solo con números y código de país**: `5491122334455`.
- **Monedas:** cotización del dólar.

### Cotización del dólar automática

En **Configuración → Monedas** se puede activar la actualización automática. El sistema lee la cotización de **lanacion.com.ar** cada cierta cantidad de horas y actualiza los precios en dólares. Si se desactiva, el valor se carga a mano.

---

## 5. Publicar en un hosting

El sistema funciona con **todo el proyecto dentro de la carpeta pública** (`htdocs/` o `public_html/`), que es lo que permiten los hostings compartidos.

1. Subí todo por FTP.
2. Creá una base de datos desde el panel del hosting e importá `database/database.sql` en su phpMyAdmin.
3. Cargá esos datos en `coneccion.php`, en el bloque `$INFINITYFREE`.
4. En el `.env`:
   ```ini
   APP_ENV=production
   APP_DEBUG=0
   APP_URL=https://tudominio.com
   ```
5. Entrá a `https://tudominio.com/compatibilidad.php` para verificar. Si está todo bien, **borrá ese archivo**.

> Las consultas del formulario **siempre se guardan** en *Panel → Consultas*, funcione o no el correo del hosting. El botón de WhatsApp anda siempre.

Para probar sin Apache:
```
php -S localhost:8000 server.php
```

---

## 6. Estructura del proyecto

```
sh-servicios/
├── index.php            Punto de entrada (todo pasa por acá)
├── .htaccess            Reescritura de URLs + protección del código
├── coneccion.php        Credenciales de la base (local e InfinityFree)
├── .env                 Configuración del entorno
├── assets/              css, js, imágenes y librerías (Bootstrap)
├── uploads/             Archivos subidos desde el panel
├── config/              Arranque, rutas y opciones de la base
├── core/                Núcleo propio: router, base de datos, auth, vistas…
├── app/
│   ├── controllers/     Sitio público  (+ admin/ para el panel)
│   ├── models/          Acceso a datos
│   ├── services/        Lógica de negocio
│   ├── helpers/         Funciones globales
│   └── views/           Plantillas (layouts, partials, admin)
├── lib/                 Generadores de PDF y Excel propios
│   (el instalador database.sql / seed.sql vive en el historial de Git, ver paso 2)
└── storage/             logs y cache
```

---

## 7. Problemas frecuentes

| Síntoma | Solución |
|---|---|
| "No se pudo conectar a la base de datos" | Revisá `coneccion.php` y que MySQL esté iniciado en XAMPP. |
| Todas las URLs dan 404 menos la portada | Falta `mod_rewrite`. En XAMPP: *httpd.conf* → activá `rewrite_module` y poné `AllowOverride All` en el `<Directory>` de `htdocs`. Reiniciá Apache. |
| No se suben las imágenes | Permisos de escritura en `uploads/` y revisar `upload_max_filesize` en `php.ini`. |
| Cambié algo en Configuración y no se ve | Recargá con Ctrl+F5 (el navegador guarda el CSS/JS viejo). |
| El logo no aparece en el panel | Subilo en *Configuración → Empresa → Logo*. |

---

## 8. Antes de publicar

1. Cambiar la contraseña del administrador.
2. Completar los datos reales de la empresa en **Configuración**.
3. Cargar el logo y las imágenes de los productos.
4. Borrar los datos de ejemplo si importaste `seed.sql`.
5. Poner `APP_DEBUG=0` y `APP_ENV=production` en el `.env`.
6. Hacer copias de seguridad periódicas de la base y de `uploads/`.
