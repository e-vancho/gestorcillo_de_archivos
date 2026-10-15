# Instalación en las carpetas confirmadas

1. Sube `app.php` a `gestorcillo-privado`, fuera de `public_html`.
2. Sube el CONTENIDO de `public/` a `public_html/gestorcillo/public/`. Conserva la carpeta `assets` y los archivos ocultos `.htaccess` y `.user.ini`. No crees otra carpeta `public` dentro de ella. No modifiques los archivos de WordPress ni `cgi-bin`.
3. Crea `setup-key.php` en `gestorcillo-privado` con este contenido, cambiando la cadena por una clave temporal aleatoria de al menos 24 caracteres (sin comillas ni barras). No la compartas ni uses la contraseña final.

```php
<?php
return 'SUSTITUYE_POR_TU_CLAVE_TEMPORAL_ALEATORIA';
```

4. Abre https://archivos.cialacla.com/instalar.php. Introduce esa clave temporal y elige usuario y contraseña compartidos (14 a 72 caracteres ASCII). La configuración se crea en la carpeta privada, con límite de 25 MiB y cuota inicial de 1 GiB. El instalador queda bloqueado al existir la configuración.
5. Borra `instalar.php` de la carpeta pública y `setup-key.php` de la privada. Borra también `comprobar.php` y `prueba.txt` si siguen ahí.
6. Abre https://archivos.cialacla.com/ e inicia sesión. Sube un PDF de prueba, copia su enlace y ábrelo en una ventana privada sin sesión. Prueba la descarga, la búsqueda, el cierre de sesión y la eliminación.
7. Comprueba que eliminar el documento invalida su enlace. Prueba un PDF real de hasta 25 MiB y confirma que un archivo superior se rechaza.
8. Comprueba que http://archivos.cialacla.com redirige a HTTPS. Si no lo hace, activa forzar HTTPS en el panel antes de usar contraseñas reales.

El entorno de desarrollo no dispone de PHP: la ejecución y sintaxis PHP siguen pendientes de comprobar en el hosting. Ya se confirmó allí PHP 8.1.34, PDO SQLite, Fileinfo, ZIP, escritura privada y límites de 128M. El diseño sigue siendo provisional.

Las rutas se calculan a partir de las carpetas confirmadas, sin necesitar la ruta absoluta de la cuenta. Los archivos privados nunca deben subirse a public_html, ni siquiera temporalmente. Haz copias privadas de toda gestorcillo-privado una vez configurada, evitando cambios concurrentes durante la copia.
