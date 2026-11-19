<?php

declare(strict_types=1);
require_once __DIR__.'/includes/responsables-service.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    http_response_code(403); exit('Este módulo está disponible solamente desde este equipo.');
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
date_default_timezone_set('America/Bogota');
session_name('parqueadero_web');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
require_once __DIR__.'/includes/auth-service.php';
$authUser=auth_require(true);
require_once __DIR__.'/includes/pagos-service.php';
function e($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$_SESSION['csrf']??=bin2hex(random_bytes(32));$csrf=$_SESSION['csrf'];
$message=$_SESSION['pagos_flash']??'';unset($_SESSION['pagos_flash']);
$error='';$actor=null;$rows=[];$selected=null;$mode='crear';$id=0;$ready=false;
$values=['nombre'=>'','descripcion'=>'','activo'=>'1'];
try{
 $actor=$authUser;
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!is_string($_POST['csrf']??null)||!hash_equals($csrf,$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('El formulario venció. Recarga la página.');}
  $action=$_POST['action']??'';
  
  if($action==='logout'){auth_logout();header('Location: login.php',true,303);exit;}
  if(!$actor){http_response_code(403);throw new InvalidArgumentException('Inicia sesión como Administrador para gestionar métodos de pago.');}
  if(!in_array($action,['crear','editar','activar','desactivar'],true))throw new InvalidArgumentException('Operación inválida.');
  $mode=$action;
  if($action!=='crear'){$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;if(!$id)throw new InvalidArgumentException('Método inválido.');}
  if(in_array($action,['activar','desactivar'],true)){
   foreach(pagos_call('sp_listar_metodos_pago',[$actor['id']]) as $row)if((int)$row['id_metodo']===$id)$selected=$row;
   if(!$selected)throw new InvalidArgumentException('El método de pago ya no existe.');
   pagos_call('sp_guardar_metodo_pago',[$actor['id'],$id,$selected['nombre'],$selected['descripcion'],$action==='activar'?1:0]);
  }else{
   foreach($values as $key=>$_)$values[$key]=is_string($_POST[$key]??null)?$_POST[$key]:'';
   pagos_call('sp_guardar_metodo_pago',array_merge([$actor['id'],$id],pagos_validar($_POST)));
  }
  $_SESSION['pagos_flash']=match($action){'crear'=>'Método de pago registrado.','editar'=>'Cambios guardados.','activar'=>'Método de pago activado.','desactivar'=>'Método de pago desactivado.'};
  header('Location: pagos.php',true,303);exit;
 }
}catch(Throwable $ex){$error=pagos_error($ex);}
if($actor)try{
 $rows=pagos_call('sp_listar_metodos_pago',[$actor['id']]);$ready=true;
 if($_SERVER['REQUEST_METHOD']==='GET'&&isset($_GET['id'])){
  $id=filter_var($_GET['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
  $mode=in_array($_GET['accion']??'',['ver','editar','activar','desactivar'],true)?$_GET['accion']:'ver';
 }
 if($id){
  $selected=null;foreach($rows as $r)if((int)$r['id_metodo']===$id){$selected=$r;break;}
  if(!$selected){$error='El método de pago ya no existe.';$id=0;$mode='crear';}
  elseif($mode==='editar'&&$_SERVER['REQUEST_METHOD']==='GET')foreach($values as $key=>$_)$values[$key]=$selected[$key]??'';
 }
}catch(Throwable $ex){$error=pagos_error($ex);}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Gestión de Métodos de pago."><title>Métodos de pago · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="pagos"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link active" href="pagos.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> M&#233;todos de pago</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Métodos de pago · Base web</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>M&#233;todos de pago</h1><p>Organiza las opciones disponibles al momento de cobrar.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<?php if($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<?php if(!$actor): ?>
<section class="card"><div class="card-head"><h2>Acceso de Administrador</h2></div><form method="post" action="pagos.php">
<input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="login">
<div class="form-grid"><label for="pay-user">Usuario<input name="usuario" id="pay-user" maxlength="50" required autocomplete="username"></label><label for="pay-pass">Contraseña<input name="clave" id="pay-pass" type="password" maxlength="128" required autocomplete="current-password"></label></div>
<div class="form-actions"><span class="form-hint">Usa una cuenta Administrador de la base web. Las cuentas se copiaron al separar las bases.</span><button class="btn primary" type="submit">Acceder a Métodos de pago</button></div></form></section>
<?php else: ?>
<section class="card crud-notice"><p>Sesión: <strong><?=e($actor['nombre'])?></strong> · Administrador</p><form method="post" action="pagos.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="logout"><button class="btn" type="submit">Cerrar sesión</button></form></section>
<?php if($selected): responsable_panel('metodo_pago',(int)$selected['id_metodo']); endif; ?>
<?php if($selected&&$mode!=='editar'): ?>
<section class="card"><div class="card-head"><h2><?=e(match($mode){'activar'=>'Activar método','desactivar'=>'Desactivar método',default=>'Detalle del método'})?></h2></div><div class="crud-detail"><p><strong>Nombre:</strong> <?=e($selected['nombre'])?></p><p><strong>Descripción:</strong> <?=e($selected['descripcion']?:'—')?></p><p><strong>Estado:</strong> <?=$selected['activo']?'Activo':'Inactivo'?></p>
<?php if(in_array($mode,['activar','desactivar'],true)): ?><p><?=$mode==='activar'?'Volverá a estar disponible al registrar salidas.':'Dejará de aparecer en nuevas salidas. Los cobros anteriores se conservan.'?></p>
<form method="post" action="pagos.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="<?=e($mode)?>"><button class="btn primary" type="submit">Confirmar</button></form><?php endif; ?>
<a class="btn" href="pagos.php">Volver al listado</a></div></section>
<?php else: ?>
<section class="card"><div class="card-head"><h2><?=$mode==='editar'?'Editar método de pago':'Registrar método de pago'?></h2></div>
<form method="post" action="pagos.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="<?=$mode==='editar'?'editar':'crear'?>">
<div class="form-grid"><label for="pay-name">Nombre<input name="nombre" id="pay-name" maxlength="50" required value="<?=e($values['nombre'])?>"></label>
<label for="pay-description">Descripción<input name="descripcion" id="pay-description" maxlength="150" value="<?=e($values['descripcion'])?>"></label>
<label for="pay-active">Estado<select name="activo" id="pay-active" required><option value="1" <?=(string)$values['activo']==='1'?'selected':''?>>Activo</option><option value="0" <?=(string)$values['activo']==='0'?'selected':''?>>Inactivo</option></select></label></div>
<div class="form-actions"><span class="form-hint">Los métodos inactivos conservan su historial de cobros.</span><a class="btn" href="pagos.php">Cancelar / limpiar</a><button class="btn primary" type="submit" <?=$ready?'':'disabled'?>>Guardar método</button></div></form></section>
<?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Métodos <span class="count" id="row-count"><?=count($rows)?></span></h2><label class="search"><input type="search" id="payment-search" aria-label="Buscar métodos" placeholder="Buscar método…"></label><label>Estado<select id="payment-filter"><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></label></div>
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="payment-rows">
<?php foreach($rows as $r): ?><tr data-status="<?=$r['activo']?'1':'0'?>"><td><?=e($r['nombre'])?></td><td><?=e($r['descripcion'])?></td><td><?=$r['activo']?'Activo':'Inactivo'?></td><td><div class="row-actions"><?php foreach(['ver'=>'Ver','editar'=>'Editar',($r['activo']?'desactivar':'activar')=>($r['activo']?'Desactivar':'Activar')] as $action=>$label): ?><a class="btn small" href="pagos.php?accion=<?=$action?>&amp;id=<?=(int)$r['id_metodo']?>" aria-label="<?=e($label.' '.$r['nombre'])?>"><?=$label?></a><?php endforeach; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div><p id="payment-empty" class="empty" <?=count($rows)?'hidden':''?>>No hay métodos que mostrar.</p><div class="table-foot"><span>Base compartida · Historial conservado</span><a href="pagos.php">Actualizar</a></div></section>
<?php endif; ?></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Métodos de pago · Base compartida</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/pagos.js" defer></script></body></html>
