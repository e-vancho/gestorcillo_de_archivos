<?php
declare(strict_types=1);
$private = dirname(__DIR__, 3) . '/gestorcillo-privado';
if (!is_file($private . '/app.php')) {
    http_response_code(503);
    exit('Falta subir app.php a gestorcillo-privado.');
}
require $private . '/app.php';
