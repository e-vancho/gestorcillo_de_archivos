# Gestorcillo

Gestión privada con una cuenta compartida y archivos públicos. Independiente de WordPress. PHP sin Composer ni servicios externos. Interfaz en español adaptable a móvil. Diseño provisional: la referencia cialacla.com se pudo consultar en texto, pero no se pudo verificar visualmente su CSS.

## Requisitos por confirmar en Donweb

- Hosting Linux con PHP 8.1 o posterior y extensiones PDO SQLite, fileinfo y ZipArchive.
- HTTPS y posibilidad de asignar la raíz del subdominio a la carpeta `public/`.
- Escritura PHP en un directorio **fuera de la raíz pública**.
- `upload_max_filesize` de al menos 25M y `post_max_size` de al menos 30M; el panel o servidor pueden imponer otros límites. `.user.ini` puede tardar varios minutos en aplicarse y no funciona con todas las modalidades PHP.

No se ha comprobado el plan del cliente: no instalar en producción hasta confirmar estos puntos. Si SQLite no está disponible, hará falta adaptar el almacenamiento a MySQL.

## Instalación

1. Sube el proyecto por SFTP o desde el panel a una carpeta independiente de WordPress. No subas `.git`, `work/` ni archivos de pruebas.
2. Configura el subdominio, por ejemplo `archivos.cialacla.com`, con su raíz en `gestorcillo/public`. **No publiques la carpeta del proyecto completa**: `config.php`, `app.php`, `bin/` y `var/` deben quedar fuera del acceso web. No se depende de `.htaccess` para protegerlos.
3. Copia `config.example.php` a `config.php`.
4. En un equipo con PHP ejecuta `php bin/password.php`. Introduce una contraseña larga y guarda el hash devuelto en `password_hash`. La contraseña se ve mientras la escribes; usa un terminal privado. El hash no es la contraseña de acceso. No incluyas la contraseña ni el hash en Git.
5. Ajusta `username`, `base_url` (URL HTTPS real sin barra final), `storage` (ruta privada), cuota y límite. La cuota inicial es de 1 GiB para los documentos; no incluye base de datos, copias ni otros archivos del hosting.
6. Permite escritura solo al usuario PHP sobre `storage`; no uses permisos 777. La aplicación crea esa carpeta y su base de datos automáticamente.
7. Activa HTTPS. Comprueba que la portada pide acceso y que las rutas `/config.php`, `/app.php`, `/var/files.sqlite` y `/bin/password.php` dan 404. Comprueba también subida, enlace desde un navegador sin sesión, descarga y eliminación con un archivo de prueba.

Sin SSH en Donweb se puede generar el hash en el equipo local y cargar `config.php` por SFTP. No hay instalador público ni credenciales predeterminadas.

## Uso

Una subida por vez. PDF, JPG, PNG, WEBP, DOCX, XLSX, PPTX, TXT y CSV. Máximo inicial: 25 MiB. Se valida extensión y MIME; Office comprueba la estructura básica del contenedor. No se admiten HTML, SVG, scripts ni archivos comprimidos genéricos. Esto no sustituye a un antivirus.

El enlace público tiene la forma `https://archivos.ejemplo.com/file.php?id=...`. Es estable mientras exista el documento, independiente del nombre y no necesita iniciar sesión. PDF e imágenes se abren en el navegador; los demás se descargan. El archivo físico se guarda con un nombre aleatorio sin extensión, fuera de la web, y se sirve mediante PHP. No necesita reglas de reescritura ni WordPress. Las peticiones Range no están implementadas en esta versión.

Eliminar requiere confirmación y rompe el enlace. No hay papelera, versionado ni sustitución de documentos en esta versión. Las copias ya descargadas por terceros no se pueden retirar. La cabecera noindex es una indicación para buscadores, no control de acceso.

La cuenta se comparte entre las dos personas: no permite atribuir acciones a cada una. La sesión caduca tras dos horas sin actividad; hay protección CSRF y un bloqueo de 15 minutos tras diez intentos fallidos por IP. Cambiar el hash invalida las sesiones anteriores.

## Copias y mantenimiento

Haz copias diarias del directorio `storage` completo (base de datos y documentos) y de `config.php` en un destino privado. Para una copia coherente, detén temporalmente las subidas/eliminaciones, copia todo y vuelve a habilitarlas. Prueba la restauración. Conserva espacio adicional para copias y revisa consumo del hosting: el tráfico de descargas también cuenta.

Mantén PHP actualizado. Los colores se cambian en las variables de `public/assets/style.css`; no se cargan fuentes, analíticas ni recursos externos.

## Prueba local

Copia la configuración, usa `base_url => http://127.0.0.1:8080` y `secure_cookie => false`, genera una contraseña y ejecuta:

```sh
php -S 127.0.0.1:8080 -t public
```

Vuelve a activar `secure_cookie => true` al instalar con HTTPS.

## Estado de validación

Se comprobó la sintaxis JavaScript. Este entorno no dispone de PHP y no permitió usar Docker; la sintaxis PHP y las pruebas funcionales de servidor quedan pendientes. No se ha desplegado en Donweb ni se han subido documentos del cliente.


## Instalación Donweb con carpetas separadas

Consulta INSTALAR-DONWEB.md. Esta distribución carga app.php desde gestorcillo-privado, tres niveles por encima de la carpeta public.
