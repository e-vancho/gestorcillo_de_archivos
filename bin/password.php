<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
fwrite(STDERR, "Contraseña (se mostrará en este terminal): ");
$password = rtrim(fgets(STDIN), "\r\n");
if (strlen($password) < 14) { fwrite(STDERR, "Usa al menos 14 caracteres.\n"); exit(1); }
echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;
