<?php
return [
    'username' => 'equipo',
    'password_hash' => 'GENERAR_CON_BIN_PASSWORD',
    'base_url' => 'https://archivos.ejemplo.com',
    'storage' => __DIR__ . '/var', // Siempre fuera de la raíz pública.
    'max_bytes' => 25 * 1024 * 1024,
    'quota_bytes' => 1024 * 1024 * 1024,
    'secure_cookie' => true, // false solo para pruebas locales HTTP.
];
