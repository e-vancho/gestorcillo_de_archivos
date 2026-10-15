<?php
declare(strict_types=1);
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
$private = dirname(__DIR__, 3).'/gestorcillo-privado';
if (is_file($private.'/config.php')) { http_response_code(404); exit('Instalación cerrada. Borra instalar.php.'); }
if (!is_file($private.'/setup-key.php')) { http_response_code(503); exit('Crea setup-key.php en la carpeta privada siguiendo INSTALAR-DONWEB.md.'); }
$key = require $private.'/setup-key.php';
if (!is_string($key) || strlen($key)<24) { http_response_code(503); exit('La clave de instalación debe tener al menos 24 caracteres.'); }
session_name('gestorcillo_setup');
ini_set('session.use_strict_mode','1');
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict','path'=>'/']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))) { http_response_code(403); exit('Recarga la página.'); }
 if (!hash_equals($key,(string)($_POST['key']??''))) { http_response_code(403); exit('Clave de instalación incorrecta.'); }
 $username=trim((string)($_POST['username']??''));
 $password=(string)($_POST['password']??'');
 if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/D',$username)) $error='Usuario: entre 3 y 60 letras, números, puntos o guiones.';
 elseif (strlen($password)<14 || strlen($password)>72) $error='Contraseña: entre 14 y 72 caracteres ASCII.';
 elseif (!hash_equals($password,(string)($_POST['repeat']??''))) $error='Las contraseñas no coinciden.';
 else {
  $config=['username'=>$username,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'base_url'=>'https://archivos.cialacla.com','storage'=>$private.'/var','max_bytes'=>25*1024*1024,'quota_bytes'=>1024*1024*1024,'secure_cookie'=>true];
  // El bloqueo exclusivo evita dos instalaciones simultáneas.
  $lock=fopen($private.'/install.lock','c');
  if (!$lock || !flock($lock,LOCK_EX)) { http_response_code(503); exit('No se pudo bloquear la instalación.'); }
  if (is_file($private.'/config.php')) { http_response_code(409); exit('Ya está instalado.'); }
  $temp=tempnam($private,'config-');
  if ($temp===false) { http_response_code(503); exit('No se pudo crear la configuración.'); }
  chmod($temp,0600);
  $content="<?php\nreturn ".var_export($config,true).";\n";
  if (file_put_contents($temp,$content)!==strlen($content) || !rename($temp,$private.'/config.php')) { @unlink($temp); http_response_code(503); exit('No se pudo guardar la configuración.'); }
  flock($lock,LOCK_UN); fclose($lock);
  session_destroy();
  echo '<h1>Instalación completada</h1><p>Borra instalar.php de la carpeta pública y setup-key.php de la privada.</p><a href="index.php">Entrar en Gestorcillo</a>'; exit;
 }
}
function e(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
?>
<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalar Gestorcillo</title><h1>Instalar Gestorcillo</h1><p>Configura el acceso compartido del equipo.</p><p role="alert"><?=e($error)?></p><form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><p><label>Clave temporal de instalación<br><input type="password" name="key" required autocomplete="off"></label></p><p><label>Usuario<br><input name="username" required autocomplete="username"></label></p><p><label>Contraseña (14–72 caracteres)<br><input type="password" name="password" required minlength="14" maxlength="72" autocomplete="new-password"></label></p><p><label>Repite la contraseña<br><input type="password" name="repeat" required autocomplete="new-password"></label></p><button>Crear acceso</button></form></html>
