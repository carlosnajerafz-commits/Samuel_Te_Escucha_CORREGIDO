# 🛡️ Reporte de Auditoría de Seguridad — Samuel Te Escucha

Este documento consolida los hallazgos de seguridad identificados tras realizar análisis estático automático y revisión manual de código del proyecto **Samuel Te Escucha** en su estado actual.

---

## 📊 Resumen de Criticidad

| Criticidad | Cantidad | Descripción |
|------------|----------|-------------|
| 🔴 **Crítica** | 2 | Vulnerabilidades explotables con facilidad que comprometen el control del servidor o exponen toda la base de datos. |
| 🟠 **Alta** | 2 | Vulnerabilidades de alto impacto como robo de control administrativo o robo de credenciales en repositorios. |
| 🟡 **Media** | 5 | Brechas de privacidad, fugas de información interna y falta de controles de tasa de peticiones (Rate Limiting). |
| 🟢 **Baja** | 1 | Configuraciones de seguridad hardening recomendadas para mitigar vectores indirectos. |

---

## 🛠️ Metodología de las Pruebas

Para identificar estas fallas se implementaron dos metodologías complementarias:
1. **Análisis Estático de Código (SAST):** Se diseñó y ejecutó un script en PHP (security_scanner.php) que escaneó las llamadas a funciones de base de datos, estructuras de autenticación, control de subida de archivos y verificación de tokens en todo el directorio público (`/public`).
2. **Revisión Manual de Código:** Se auditaron de forma exhaustiva scripts críticos de bases de datos (`init.sql`, `db.php`), archivos de configuración de infraestructura (`docker-compose.yml`) y utilidades del sistema (`backup_db.sh`).

---

## 🔍 Detalle de Hallazgos de Seguridad

### SEC-01: Ausencia General de Protección CSRF (Cross-Site Request Forgery)
* **Criticidad:** 🔴 Crítica
* **Archivos Afectados:**
  * Prácticamente todos los archivos de administración y acciones que procesan peticiones POST:
    * actualizar_categoria_comedor.php
    * actualizar_estatus_cita.php
    * actualizar_estatus_comedor.php
    * actualizar_estatus_queja.php
    * alta_apoyo.php
    * eliminar_apoyo.php
    * eliminar_bloqueo.php
    * eliminar_cita.php
    * eliminar_evento_comedor.php
    * eliminar_queja.php
    * eliminar_registro_apoyo.php
    * eliminar_registro_comedor.php
    * guardar_apoyo.php
    * guardar_bloqueo.php
    * guardar_cita.php
    * guardar_comedor.php
    * guardar_cp.php
    * guardar_evento_comedor.php
    * guardar_queja.php
    * toggle_maintenance.php
* **Descripción:**
  A pesar de contar con el archivo de protección de tokens `csrf.php` y haberlo integrado en el formulario de acceso (`login.php`), ninguno de los demás endpoints del panel de control que alteran, eliminan o insertan datos valida la presencia del token CSRF.
* **Vector de Ataque (PoC):**
  Un atacante diseña un sitio web malicioso o envía un correo con un formulario HTML oculto apuntando a `eliminar_cita.php` con el parámetro `id=123`. Si un empleado administrativo logueado en la plataforma visita el sitio del atacante, el navegador del empleado enviará automáticamente la petición de borrado de forma transparente, destruyendo el registro sin su consentimiento.
* **Mitigación:**
  1. Integrar el token CSRF en todos los formularios mediante `<?php echo csrf_field(); ?>`.
  2. Al inicio de cada script de acción que procese peticiones POST, validar el token llamando a `csrf_validate()`.

---

### SEC-02: Base de Datos y Respaldos Almacenados en Directorio Web Público
* **Criticidad:** 🔴 Crítica
* **Archivos Afectados:**
  * `backup_db.sh` (Líneas 4 y 5)
* **Descripción:**
  El script encargado de realizar los backups automáticos del sistema almacena los archivos SQL directamente en `/var/www/html/backups/`. Debido a que `/var/www/html` es la raíz de Apache mapeada públicamente a Internet, cualquier persona puede descargar directamente el respaldo completo de la base de datos si conoce o adivina el nombre del archivo.
  Además, los nombres de archivos siguen un formato altamente predecible: `tecamac_%Y-%m-%d_%H-%M-%S.sql`.
* **Vector de Ataque (PoC):**
  Si el atacante sabe que la tarea cron de respaldo corre cada noche a las 02:00:00, puede enviar peticiones de fuerza bruta a URLs como `https://samuelteescucha.com/backups/tecamac_2026-06-25_02-00-00.sql` ajustando levemente los segundos. Al obtener el archivo, tendrá acceso a todos los datos de ciudadanos, incluyendo direcciones, teléfonos, correos y hashes de contraseñas de personal administrativo.
* **Mitigación:**
  1. Cambiar la ubicación de almacenamiento del backup a una ruta fuera del directorio público (ej. `/var/www/backups`).
  2. Bloquear el acceso HTTP al directorio backups mediante directivas en `.htaccess` si por alguna razón debe mantenerse allí.

---

### SEC-03: Credenciales de Base de Datos Escritas Directamente en el Código Fuente
* **Criticidad:** 🟠 Alta
* **Archivos Afectados:**
  * `public/db.php` (Línea 19)
  * `public/backup_db.sh` (Línea 9)
  * `docker-compose.yml` (Líneas 24-26)
  * `temp/docker-compose.yml` (Líneas 20-22)
* **Descripción:**
  Se utilizan valores de credenciales por defecto escritas directamente en el código fuente (hardcoded): `tecamac_user`, `tecamac_pass` y `tecamac_password`.
* **Vector de Ataque (PoC):**
  Al publicar este proyecto en un repositorio público de Git (ej. GitHub), las credenciales de la base de datos de producción quedan expuestas para cualquier actor malicioso que analice el historial de commits.
* **Mitigación:**
  1. Asegurar que las variables en `db.php` se obtengan exclusivamente del entorno (`getenv()`), sin dejar valores por defecto sensibles.
  2. Modificar el archivo `backup_db.sh` para leer la variable `PGPASSWORD` desde el entorno o un archivo de configuración restringido externamente.

---

### SEC-04: Cuenta Administrativa por Defecto e Insegura en Script SQL
* **Criticidad:** 🟠 Alta
* **Archivos Afectados:**
  * `init.sql` (Líneas 150-156)
* **Descripción:**
  El script de inicialización de la base de datos contiene una inserción directa (`INSERT INTO empleados`) para la cuenta `empleado01` comentando explícitamente su contraseña en texto plano (`password: UWuCzz5863!`).
* **Vector de Ataque (PoC):**
  Si el sistema es desplegado usando la base de datos inicial sin modificaciones, cualquier persona en Internet que descubra la ruta `/login.php` podrá autenticarse usando el usuario `empleado01` y la contraseña descrita en el archivo público del repositorio.
* **Mitigación:**
  1. Eliminar la inserción por defecto en `init.sql`.
  2. Implementar un script interactivo de línea de comando (CLI) o una interfaz segura y efímera para la creación del primer usuario administrativo durante la instalación inicial.

---

### SEC-05: Exposición de Mensajes de Error Internos del Motor SQL (Fuga de Información)
* **Criticidad:** 🟡 Media
* **Archivos Afectados:**
  * `public/db.php` (Línea 32)
  * `public/eliminar_cita.php` (Línea 32)
  * `public/eliminar_queja.php` (Línea 32)
  * `public/eliminar_apoyo.php` (Línea 36)
  * `public/eliminar_registro_apoyo.php` (Línea 32)
  * `public/actualizar_estatus_queja.php` (Línea 36)
* **Descripción:**
  En varios bloques `try/catch` de interacción con la base de datos, el script ejecuta un `die("Error...: " . $e->getMessage())` si falla la query. Esto imprime en pantalla la excepción nativa de PDO.
* **Vector de Ataque (PoC):**
  Un atacante puede generar errores deliberados (enviando tipos de datos incorrectos o strings en campos numéricos) y la respuesta del servidor le revelará nombres exactos de tablas, columnas, restricciones de llave externa y detalles sobre la estructura de la base de datos de PostgreSQL, facilitando la construcción de ataques avanzados.
* **Mitigación:**
  Reemplazar la salida de `$e->getMessage()` por un mensaje de error genérico para el usuario (ej: *"Ocurrió un problema en el servidor. Intente más tarde"*), guardando el detalle técnico únicamente en los registros de errores del servidor (`error_log()`).

---

### SEC-06: Carga de Archivos de Evidencia sin Validación MIME (Insecure File Upload)
* **Criticidad:** 🟡 Media
* **Archivos Afectados:**
  * `public/guardar_queja.php` (Línea 71-77)
* **Descripción:**
  El validador de subida de archivos de evidencias evalúa únicamente la extensión final del nombre provisto por el usuario:
  `$ext = strtolower(pathinfo($_FILES["evidencia"]["name"], PATHINFO_EXTENSION));`
  No se utiliza ninguna biblioteca o función nativa de validación del contenido real del archivo (como `mime_content_type()` o la extensión `Fileinfo`).
* **Vector de Ataque (PoC):**
  Un atacante puede renombrar un archivo con extensión permitida (ej: `script.php.png` o subir un archivo que contenga código PHP dentro de los metadatos EXIF de un archivo `.png`). Si el servidor Apache está configurado incorrectamente o cuenta con directivas de reescritura inseguras, podría llegar a ejecutar el script del atacante al intentar acceder a la imagen.
* **Mitigación:**
  Validar el tipo MIME real del archivo utilizando `finfo_file()` o `mime_content_type()`, y garantizar que pertenezca a la lista de tipos MIME seguros aprobados (ej: `image/jpeg`, `image/png`, `image/webp`).

---

### SEC-07: Predictibilidad de Archivos de Evidencia y Falta de Control de Acceso
* **Criticidad:** 🟡 Media
* **Archivos Afectados:**
  * Directorio público: `public/uploads/`
* **Descripción:**
  Los nombres de los archivos subidos (incluyendo identificaciones de ciudadanos) son generados usando la función `uniqid("queja_", true)`. Esta función no es de carácter seguro criptográfico, sino que se basa en la marca de tiempo del sistema.
  Adicionalmente, el directorio `uploads/` se encuentra expuesto públicamente sin control de sesiones; cualquier persona que adivine la URL puede descargar la evidencia.
* **Vector de Ataque (PoC):**
  Un atacante analiza la hora aproximada en que se registró una queja y calcula el valor arrojado por `uniqid()`. A través de un script automatizado, solicita combinaciones de marcas de tiempo y logra acceder a documentos privados de identificación (como credenciales de elector) almacenados en el directorio `uploads/`.
* **Mitigación:**
  1. Emplear generadores de cadenas seguras aleatorias (como `bin2hex(random_bytes(16))`) para renombrar los archivos subidos.
  2. Mover el directorio `uploads/` fuera de la raíz web pública, y servir los archivos a través de un controlador PHP intermedio que verifique la autenticación del empleado antes de enviar el archivo (`fpassthru()`).

---

### SEC-08: Ausencia de Rate Limiting en Formularios Ciudadanos Públicos
* **Criticidad:** 🟡 Media
* **Archivos Afectados:**
  * Formularios públicos y sus receptores:
    * `cita.php` / `guardar_cita.php`
    * `queja.php` / `guardar_queja.php`
    * `comedor.php` / `guardar_comedor.php`
    * `apoyos.php` / `guardar_apoyo.php`
* **Descripción:**
  No existen límites de velocidad (Rate Limiting) para peticiones en los formularios abiertos a la ciudadanía. Aunque se ha creado la migración para almacenar límites, su uso no ha sido integrado en estos scripts.
* **Vector de Ataque (PoC):**
  Un bot puede enviar de manera automatizada decenas de miles de solicitudes de citas simuladas, consumiendo y bloqueando todos los horarios de atención disponibles de forma indefinida, provocando un ataque de Denegación de Servicio (DoS) lógico.
* **Mitigación:**
  Incluir el módulo `rate_limit.php` en los controladores públicos y validar que la IP del cliente no exceda el número razonable de envíos por hora (ej. 3 registros por hora).

---

### SEC-09: Ausencia de Headers de Seguridad HTTP y Atributos en Cookies de Sesión
* **Criticidad:** 🟡 Media / 🟢 Baja
* **Archivos Afectados:**
  * Global (Todas las vistas, exceptuando parcialmente a `login.php`).
* **Descripción:**
  No se aplican cabeceras de respuesta HTTP para mitigar vulnerabilidades comunes (como `X-Frame-Options` para evitar Clickjacking o `Content-Security-Policy` contra XSS).
  Asimismo, la cookie de sesión de PHP (`PHPSESSID`) se inicializa con los valores por defecto del sistema, los cuales comúnmente carecen de los flags `HttpOnly`, `Secure` y `SameSite`.
* **Vector de Ataque (PoC):**
  Un atacante incrusta el dashboard de administración dentro de un `iframe` transparente en un sitio externo (Clickjacking) para forzar al empleado a hacer clic en botones de eliminación sin saberlo. También, si existe una vulnerabilidad XSS en el navegador, un script malicioso puede leer la cookie de sesión (por falta de `HttpOnly`) y secuestrar la cuenta del empleado.
* **Mitigación:**
  1. Asegurar que `includes/security_headers.php` sea incluido al inicio de todos los archivos PHP que rendericen contenido HTML.
  2. Configurar los parámetros de sesión en `config.php` antes de `session_start()` utilizando:
     ```php
     session_start([
         'cookie_httponly' => true,
         'cookie_secure'   => true, // Requiere HTTPS
         'cookie_samesite' => 'Lax'
     ]);
     ```

---

## 🟢 Buenas Prácticas Encontradas

A pesar de los hallazgos anteriores, se reconocen buenas prácticas de seguridad ya implementadas:
1. **Sentencias Preparadas (Prepared Statements):** La gran mayoría de consultas SQL utilizan enlaces seguros con PDO (`:param`), previniendo de forma efectiva ataques clásicos de Inyección SQL.
2. **Encriptación de Contraseñas:** Se utiliza `bcrypt` mediante las funciones nativas `password_hash()` y `password_verify()`.
3. **Regeneración de Sesiones:** El proceso de Login regenera el identificador de sesión a través de `session_regenerate_id(true)`, impidiendo ataques de fijación de sesión.
4. **Prevención básica de XSS:** Se realiza uso regular de la función `htmlspecialchars()` al imprimir variables en las vistas del panel de control.
