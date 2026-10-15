<?php
require __DIR__.'/bootstrap.php';
sessionStart();
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 csrf(); $action=(string)($_POST['action']??'');
 if ($action==='login') {
  $key=hash('sha256', (string)($_SERVER['REMOTE_ADDR']??'unknown'));
  $db->beginTransaction();
  $q=$db->prepare('SELECT * FROM attempts WHERE id=?'); $q->execute([$key]); $a=$q->fetch();
  if ($a && $a['until']>time() && $a['count']>=10) { $db->commit(); $error='Demasiados intentos. Espera 15 minutos.'; }
  else {
   $valid=password_verify((string)($_POST['password']??''),$c['password_hash']) && hash_equals($c['username'], (string)($_POST['username']??''));
   if ($valid) { $db->prepare('DELETE FROM attempts WHERE id=?')->execute([$key]); session_regenerate_id(true); $_SESSION['auth']=hash('sha256',$c['password_hash']); $_SESSION['csrf']=bin2hex(random_bytes(32)); }
   else { $count=($a && $a['until']>time()) ? $a['count']+1 : 1; $db->prepare('INSERT OR REPLACE INTO attempts VALUES (?,?,?)')->execute([$key,$count,time()+900]); $error='Usuario o contraseña incorrectos.'; }
   $db->prepare('DELETE FROM attempts WHERE until < ?')->execute([time()]); $db->commit();
  }
 } else {
  if (!isset($_SESSION['auth']) || !hash_equals(hash('sha256',$c['password_hash']), $_SESSION['auth'])) fail(401,'Inicia sesión.');
  if ($action==='logout') { $_SESSION=[]; session_destroy(); header('Location: index.php'); exit; }
  try {
   if ($action==='upload') {
    $f=$_FILES['file']??null;
    if (!$f || $f['error']!==UPLOAD_ERR_OK) throw new RuntimeException('No se pudo recibir el archivo. Revisa el tamaño y los límites del servidor.');
    $size=filesize($f['tmp_name']);
    if ($size<1 || $size>$c['max_bytes']) throw new RuntimeException('El archivo está vacío o supera '.bytes($c['max_bytes']).'.');
    $name=basename(str_replace('\\','/',$f['name']));
    $name=preg_replace('/[\x00-\x1f\x7f]/','', $name);
    if (strlen($name)>200) throw new RuntimeException('El nombre es demasiado largo.');
    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    $types=['pdf'=>['application/pdf'],'jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],'txt'=>['text/plain'],'csv'=>['text/plain','text/csv'],'docx'=>['application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],'xlsx'=>['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],'pptx'=>['application/zip','application/vnd.openxmlformats-officedocument.presentationml.presentation']];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$ext]) || !in_array($mime,$types[$ext],true)) throw new RuntimeException('Tipo de archivo no permitido o contenido no válido.');
    if (in_array($ext,['docx','xlsx','pptx'],true)) {
     $z=new ZipArchive();
     if ($z->open($f['tmp_name'])!==true) throw new RuntimeException('Documento de Office inválido.');
     $entry=['docx'=>'word/document.xml','xlsx'=>'xl/workbook.xml','pptx'=>'ppt/presentation.xml'][$ext];
     $valid=$z->locateName('[Content_Types].xml')!==false && $z->locateName($entry)!==false;
     $z->close(); if (!$valid) throw new RuntimeException('Documento de Office inválido.'); $mime=$types[$ext][1];
    }
    $id=bin2hex(random_bytes(16));
    $db->exec('BEGIN IMMEDIATE');
    try {
     if ((int)$db->query('SELECT COALESCE(SUM(size),0) FROM files')->fetchColumn()+$size>$c['quota_bytes']) throw new RuntimeException('Se ha alcanzado el espacio asignado.');
     if (!move_uploaded_file($f['tmp_name'],$store.'/'.$id)) throw new RuntimeException('No se pudo guardar el archivo.');
     chmod($store.'/'.$id,0600);
     $db->prepare('INSERT INTO files VALUES (?,?,?,?,?)')->execute([$id,$name,$mime,$size,time()]);
     $db->exec('COMMIT');
    } catch (Throwable $ex) { $db->exec('ROLLBACK'); if (is_file($store.'/'.$id)) unlink($store.'/'.$id); throw $ex; }
    $_SESSION['notice']='Archivo subido. Ya puedes copiar su enlace.';
   } elseif ($action==='delete') {
    $id=(string)($_POST['id']??'');
    if (!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new RuntimeException('Archivo inválido.');
    $db->exec('BEGIN IMMEDIATE');
    try {
     if (is_file($store.'/'.$id) && !unlink($store.'/'.$id)) throw new RuntimeException('No se pudo eliminar.');
     $db->prepare('DELETE FROM files WHERE id=?')->execute([$id]); $db->exec('COMMIT');
    } catch (Throwable $ex) { $db->exec('ROLLBACK'); throw $ex; }
    $_SESSION['notice']='Archivo eliminado. Su enlace dejará de funcionar.';
   }
   header('Location: index.php'); exit;
  } catch (PDOException $ex) { error_log($ex->getMessage()); $error='No se pudo completar la operación. Revisa el almacenamiento del servidor.'; } catch (RuntimeException $ex) { $error=$ex->getMessage(); }
 }
}
$auth=isset($_SESSION['auth']) && hash_equals(hash('sha256',$c['password_hash']),$_SESSION['auth']);
$files=$auth ? $db->query('SELECT * FROM files ORDER BY created DESC, id DESC')->fetchAll() : [];
$total=array_sum(array_column($files,'size'));
$notice=$_SESSION['notice']??''; unset($_SESSION['notice']);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gestorcillo · Documentos</title><link rel="stylesheet" href="assets/style.css"><script src="assets/app.js" defer></script></head><body>
<header><a class="brand" href="index.php">gestorcillo<span>ARCHIVO DIGITAL</span></a><span class="private">● Gestión privada</span><?php if($auth): ?><form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><button class="quiet" name="action" value="logout">Cerrar sesión ↗</button></form><?php endif ?></header>
<main><?php if($error): ?><p role="alert" class="alert"><?=e($error)?></p><?php endif ?><?php if($notice): ?><p role="status" class="notice"><?=e($notice)?></p><?php endif ?>
<?php if(!$auth): ?><section class="login"><p class="eyebrow">ESPACIO DEL EQUIPO</p><h1>Todo a mano.</h1><p class="muted">Entra para gestionar y compartir tus documentos.</p><form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="action" value="login"><label>Usuario<input name="username" autocomplete="username" required maxlength="100"></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required maxlength="1000"></label><button class="primary">Entrar →</button></form></section>
<?php else: ?><p class="eyebrow">TU BIBLIOTECA COMPARTIDA</p><div class="heading"><div><h1>Documentos,<br>listos para compartir.</h1><p class="muted">Sube un archivo. Copia su enlace. Llévalo donde quieras.</p></div><div class="stats"><strong><?=count($files)?></strong> archivos <span><?=bytes($total)?> de <?=bytes($c['quota_bytes'])?></span></div></div>
<section class="upload"><div><span class="upload-icon">↑</span><h2>Un nuevo documento</h2><p>Arrastra un archivo aquí o selecciónalo.<br><small>PDF, imágenes, Office, TXT y CSV · Hasta <?=bytes($c['max_bytes'])?></small></p></div><form method="post" enctype="multipart/form-data" id="upload-form"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="action" value="upload"><label class="file-label">Seleccionar archivo<input type="file" name="file" id="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx,.pptx,.txt,.csv"></label><p id="selected" aria-live="polite">Ningún archivo seleccionado</p><button class="primary" id="upload-button">Subir archivo ↗</button></form></section>
<div class="list-heading"><h2>Todos los archivos <span><?=count($files)?></span></h2><input type="search" id="search" placeholder="Buscar por nombre…" aria-label="Buscar archivos"></div><p class="public-note">Los enlaces son públicos: cualquier persona que tenga uno puede abrir el archivo.</p>
<div class="files"><?php foreach($files as $f): ?><article class="file-row" data-name="<?=e($f['name'])?>"><span class="type"><?=e(strtoupper(pathinfo($f['name'],PATHINFO_EXTENSION)))?></span><div class="file-info"><a href="<?=e(url($f))?>" target="_blank" rel="noopener"><?=e($f['name'])?></a><small><?=bytes($f['size'])?> · <?=gmdate('d/m/Y',$f['created'])?></small></div><div class="actions"><button type="button" class="copy" data-url="<?=e(url($f))?>">Copiar enlace</button><a class="download" href="<?=e(url($f,true))?>">Descargar ↓</a><form method="post" class="delete-form" data-filename="<?=e($f['name'])?>"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="id" value="<?=e($f['id'])?>"><button class="delete" name="action" value="delete" aria-label="Eliminar <?=e($f['name'])?>">×</button></form></div></article><?php endforeach ?><p id="empty" class="empty" <?=count($files)?'hidden':''?>><?=count($files)?'No hay coincidencias.':'Tu biblioteca empieza aquí. Sube tu primer documento.'?></p></div><p id="feedback" role="status" aria-live="polite"></p>
<?php endif ?></main><footer>gestorcillo <span>Un lugar para tus documentos.</span></footer></body></html>
