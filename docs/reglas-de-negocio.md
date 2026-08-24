# Reglas de negocio y operación — Sistema de Trazabilidad de Café

> Documento de referencia rápida: infraestructura, despliegue y decisiones operativas del sistema. Se actualiza a medida que el proyecto avanza.

## Repositorio

- **URL:** https://github.com/harri28/trazabilidad_cafe
- **Rama principal (default en GitHub):** `main`
- **Despliegue:** manual. No hay CI/CD ni webhooks automáticos — el `git pull` en el VPS se ejecuta a mano cuando se quiere actualizar el servidor con los últimos cambios.
- `.gitignore` excluye `*.sql` por defecto. Los archivos de esquema que sí deben versionarse (ver abajo) se agregan con `git add -f`.

## VPS (producción) — **sistema en vivo desde 2026-08-24**

- **IP real:** `161.132.51.134` (⚠️ no confundir con `2.57.91.91`, una IP de parking de Hostinger que no corresponde a este servidor — se detectó y corrigió el registro DNS)
- **Hostname:** `sv-O8EUkgxLPR3n9lhvWIep`
- **SO:** Ubuntu 20.04.6 LTS "focal", Apache `2.4.41`
- **Acceso:** SSH como `root`, puerto 22 restringido por firewall a IPs específicas (no alcanzable desde cualquier red).
- **Ruta del proyecto:** `/var/www/trazabilidad_cafe`
- **Dominio del sistema:** `https://trazabcafe.cloud` (y `www.`) — DNS configurado (`A` en `@` → `161.132.51.134`), Apache vhost creado, **SSL activo con Let's Encrypt** (certbot, vence 2026-11-22, autorrenovación vía `certbot renew`, ya configurado como cron/systemd timer por certbot mismo).

### PHP: se compiló desde código fuente — no instalar por apt

El VPS traía solo **PHP 7.4** (vía `mod_php`, usado por todos los demás sitios). El proyecto requiere PHP 8.0+. El PPA `ondrej/php` **ya no publica paquetes de PHP 8.x para Ubuntu 20.04 "focal"** (Packages.gz vacío, confirmado) — no intentar `apt install php8.x-fpm`, no va a funcionar.

En su lugar, PHP **8.2.12 está compilado desde código fuente**:
- Prefix: `/usr/local/php8.2`
- Corre como **PHP-FPM** (no mod_php), servicio systemd propio: `php8.2-fpm`
- Socket: `/run/php/php8.2-fpm.sock` (owner `www-data`)
- El PHP 7.4 del sistema **no se tocó** — sigue sirviendo a farmacia/industria_mg/kallparoom normalmente.
- El servicio systemd (`/etc/systemd/system/php8.2-fpm.service`) tenía por defecto `ProtectSystem=full`, lo cual bloqueaba la escritura de logs/PID bajo `/usr/local/php8.2` (todo lo que cuelga de `/usr` queda de solo lectura con esa directiva) — **se eliminó esa línea**. Si se reinstala o reconfigura el servicio en el futuro, recordar quitarla de nuevo.
- Para recompilar o actualizar: fuente en `/usr/local/src/php-8.2.12/`.

### Vhosts de Apache — cada proyecto con su propio dominio

No hay un dominio compartido con rutas por proyecto (a diferencia del XAMPP local) — cada uno tiene dominio y vhost propios:

| Dominio | Proyecto | PHP |
|---|---|---|
| `genpharma.cloud` | farmacia | 7.4 (mod_php) |
| `kallparoom.cloud` | kallparoom (Laravel/Aimeos) | corre en Docker (contenedor propio con su PHP) |
| `sistemmg.com` | industria_mg | 7.4 (mod_php) |
| `corfiemsistem.com` | structure (Django, proxy a `127.0.0.1:8000`) | N/A |
| `trazabcafe.cloud` | **trazabilidad_cafe** (este sistema) | **8.2.12 (PHP-FPM propio)** |

Vhost de este proyecto: `/etc/apache2/sites-available/trazabcafe.cloud.conf` (HTTP→HTTPS redirect, generado por certbot) + `trazabcafe.cloud-le-ssl.conf` (HTTPS, con el `<FilesMatch \.php$>` que enruta a PHP-FPM). `DocumentRoot` es la raíz del proyecto (`/var/www/trazabilidad_cafe`), con `Options -Indexes` (el listado de directorios estaba expuesto públicamente al inicio — incluía los `.docx`/`.xlsx` internos — ya corregido). La raíz (`/`) redirige a `/public/` (login del sistema) vía `RedirectMatch ^/$ /public/` en el vhost SSL.

## Base de datos

- **Motor:** PostgreSQL (ya instalado y activo en el VPS)
- **Base:** `trazabilidad_cafe`
- **Usuario/contraseña:** `postgresql` / `1234` (mismas credenciales en local y en VPS, por decisión explícita — no son seguras para un entorno público, pero se mantiene simple a propósito)

### ⚠️ Esquema real: `acopios`, no `lotes`

El nombre de entidad original era "Lote" (tabla `lotes`), pero el sistema migró la terminología de negocio a **"Acopio"**. La base de datos real usa:

- Tabla `acopios` (antes `lotes`)
- Tabla `acopio_certificaciones` (antes `lote_certificaciones`)
- Tabla `acopio_eventos` (antes `lote_eventos`) — bitácora de trazabilidad
- Columna `acopio_id` (antes `lote_id`) en `kardex`, `laboratorio_analisis`, `ventas`, `cotizaciones`, `ordenes_trabajo`, `transformaciones` (`acopio_origen_id`/`acopio_destino_id`)
- Vista `v_acopios_completos` (antes `v_lotes_completos`)
- Controlador vivo: `AcopioController.php` (rutas `/acopios`). `LoteController.php` sigue en el repo pero **ya no está enrutado** — es código muerto.

Los archivos `database/schema_pg.sql`, `schema_v2_pg.sql`, etc. **siguen creando la tabla vieja `lotes`** y están desactualizados. Para inicializar una base de datos nueva, usar:

```bash
psql -U postgresql -d trazabilidad_cafe -f database/schema_acopios.sql   # estructura completa (pg_dump --schema-only de la BD real)
psql -U postgresql -d trazabilidad_cafe -f database/catalogos_seed.sql   # catálogos base: tipos_cafe, certificaciones_catalogo
```

El VPS arrancó limpio (sin datos de prueba de clientes/acopios/ventas) — solo estructura + catálogos.

## Bugs de portabilidad Windows → Linux encontrados en el despliegue

Código que funcionaba en el XAMPP local (Windows, filesystem case-insensitive, PHP 8.2) pero fallaba en el VPS (Linux, case-sensitive):

1. **`api/config/database.php` → renombrado a `Database.php`**: la clase se llama `Database`, el autoloader busca el archivo exacto por nombre de clase. En Windows no importaba; en Linux tiraba `Class "Database" not found` en cualquier endpoint.
2. **`DashboardController.php`**: llamaba `Database::getConnection()` como método estático (no lo es). En PHP 7.4 esto era solo un warning; en PHP 8 es un `Error` fatal — se corrigió a `(new Database())->getConnection()`.
3. **`Router.php`**: tenía hardcodeado el prefijo `/trazabilidad_cafe/api` (asumía que la app siempre vive en esa subcarpeta, como en XAMPP local). Con dominio propio (`DocumentRoot` = raíz del proyecto), la URL es solo `/api/...` y el prefijo nunca calzaba → 404 en todas las rutas. Se generalizó para soportar ambos casos.

Si se detectan más errores solo-en-Linux al seguir desarrollando, revisar primero mayúsculas/minúsculas de nombres de archivo y funciones exclusivas de PHP 8 usadas de forma incorrecta (llamadas estáticas a métodos de instancia, etc.).

## Comandos esenciales

**Clonar el proyecto en el VPS (ya hecho, para referencia futura / otro servidor):**
```bash
cd /var/www/trazabilidad_cafe
git clone https://github.com/harri28/trazabilidad_cafe.git .
```

**Actualizar el VPS con los últimos cambios (manual, sin CI/CD):**
```bash
cd /var/www/trazabilidad_cafe
git pull origin main
```

**Reiniciar PHP-FPM tras cambios de configuración:**
```bash
systemctl restart php8.2-fpm
systemctl status php8.2-fpm --no-pager
```

**Renovar SSL manualmente (normalmente automático):**
```bash
certbot renew
```

## Estado del despliegue en VPS

- [x] Código en GitHub actualizado con el esquema `acopios` (rama `main`)
- [x] `database/schema_acopios.sql` y `database/catalogos_seed.sql` disponibles en el repo
- [x] Repo clonado en `/var/www/trazabilidad_cafe`
- [x] Base de datos creada y esquema + catálogos cargados (limpia, sin datos de prueba)
- [x] PHP 8.2.12 compilado e instalado como PHP-FPM propio (sin afectar el PHP 7.4 del sistema)
- [x] Vhost de Apache para `trazabcafe.cloud` con `Options -Indexes`
- [x] Certificado SSL con Let's Encrypt (HTTPS + redirect automático desde HTTP)
- [x] Bugs de portabilidad Windows→Linux corregidos (`Database.php`, `Router.php`, `DashboardController.php`)
- [x] `https://trazabcafe.cloud/api/` respondiendo correctamente
- [ ] Pendiente: el VPS reportó `*** System restart required ***` (por actualizaciones del sistema, no relacionado a este despliegue) — reiniciar en horario de bajo tráfico cuando se pueda.
