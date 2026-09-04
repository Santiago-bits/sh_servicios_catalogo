# Dependencias de terceros

El proyecto **no usa Composer ni npm**: las librerías de front-end están
vendorizadas a mano en `assets/vendor/`. Como no hay `composer.lock` /
`package-lock.json`, no hay escaneo automático de vulnerabilidades (SCA).

**Revisar este archivo en cada release** y comparar cada versión contra el
aviso de seguridad del proyecto correspondiente.

| Librería | Versión | Ubicación | Fuente | Última revisión |
|---|---|---|---|---|
| Bootstrap (CSS + bundle JS) | 5.3.3 | `assets/vendor/bootstrap/` | https://github.com/twbs/bootstrap/releases | 2026-09-03 |
| Bootstrap Icons (font) | — | `assets/vendor/bootstrap-icons/` | https://github.com/twbs/icons/releases | 2026-09-03 |
| Chart.js (UMD) | 4.4.3 | `assets/vendor/chartjs/chart.umd.min.js` | https://github.com/chartjs/Chart.js/releases | 2026-09-03 (sólo lo usa la sección Estadísticas, hoy oculta) |

## PHP

- Runtime: **PHP 8.1+**. Extensiones requeridas: `pdo_mysql`, `mbstring`,
  `dom`, `gd`, `curl`, `fileinfo`, `json`. Opcional: `zip` (exportar a XLSX;
  si falta, se entrega CSV).
- Sin paquetes Composer. Generadores propios de PDF/Excel en `lib/`.

## Cómo actualizar una librería vendorizada

1. Descargar la versión nueva del release oficial (no de un CDN de terceros).
2. Reemplazar los archivos en `assets/vendor/<lib>/`.
3. Actualizar la fila de la tabla (versión + fecha).
4. Probar el sitio (el `?v=` de `asset()` fuerza recarga del cache).
