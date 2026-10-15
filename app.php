<?php
declare(strict_types=1);
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
header('X-Robots-Tag: noindex, nofollow, noarchive');
function fail(int $status, string $message): void { http_response_code($status); exit($message); }
$configFile = __DIR__.'/config.php';
if (!is_file($configFile)) fail(503, 'Gestorcillo pendiente de configurar. Consulta README.md.');
$c = require $configFile;
if (!password_get_info($c['password_hash'])['algo']) fail(503, 'Configura una contraseña válida.');
$store = $c['storage'];
if (!is_dir($store) && !mkdir($store, 0700, true)) fail(503, 'Almacenamiento no disponible.');
$db = new PDO('sqlite:'.$store.'/files.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA busy_timeout=5000; CREATE TABLE IF NOT EXISTS files (id TEXT PRIMARY KEY, name TEXT NOT NULL, mime TEXT NOT NULL, size INTEGER NOT NULL, created INTEGER NOT NULL); CREATE TABLE IF NOT EXISTS attempts (id TEXT PRIMARY KEY, count INTEGER NOT NULL, until INTEGER NOT NULL)');
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url(array $file, bool $download = false): string { global $c; return rtrim($c['base_url'], '/').'/file.php?id='.$file['id'].($download ? '&download=1' : ''); }
function bytes(int $n): string { return $n >= 1048576 ? number_format($n/1048576, 1, ',', '.').' MB' : number_format($n/1024, 0, ',', '.').' KB'; }
function sessionStart(): void {
 global $c;
 session_name('gestorcillo');
 session_set_cookie_params(['httponly'=>true, 'secure'=>$c['secure_cookie'], 'samesite'=>'Strict', 'path'=>'/']);
 ini_set('session.use_strict_mode', '1'); session_start();
 if (isset($_SESSION['last']) && time()-$_SESSION['last'] > 7200) $_SESSION=[];
 $_SESSION['last']=time();
 $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
 header('Cache-Control: no-store');
}
function csrf(): void { if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) fail(403, 'Sesión caducada. Recarga la página.'); }
