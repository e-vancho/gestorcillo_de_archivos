<?php
require __DIR__.'/bootstrap.php';
$id = (string)($_GET['id'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/D', $id)) fail(404, 'Archivo no encontrado.');
$q=$db->prepare('SELECT * FROM files WHERE id=?'); $q->execute([$id]); $f=$q->fetch();
if (!$f || !is_file($store.'/'.$id)) fail(404, 'Archivo no encontrado.');
header('Content-Type: '.$f['mime']);
header('Content-Length: '.filesize($store.'/'.$id));
$inline = in_array($f['mime'], ['application/pdf','image/jpeg','image/png','image/webp'], true) && !isset($_GET['download']);
header('Content-Disposition: '.($inline ? 'inline' : 'attachment').'; filename="documento.'.pathinfo($f['name'], PATHINFO_EXTENSION).'"; filename*=UTF-8\'\''.rawurlencode($f['name']));
header('Cache-Control: public, max-age=0, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile($store.'/'.$id);
