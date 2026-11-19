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
require_once __DIR__.'/includes/espacios-service.php';
function e($value):string{return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$_SESSION['csrf']??=bin2hex(random_bytes(32));$csrf=$_SESSION['csrf'];
$message=$_SESSION['espacio_flash']??'';unset($_SESSION['espacio_flash']);
$error='';$rows=[];$types=[];$used=[];$selected=null;$id=0;$mode='crear';$ready=false;
$values=['numero'=>'','tipo'=>''];
try{
 $types=array_column(espacios_call('sp_listar_tipos_vehiculo'),'nombre');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!is_string($_POST['csrf']??null)||!hash_equals($csrf,$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('El formulario venció. Recarga la página.');}
  $mode=$_POST['action']??'';
  if(!in_array($mode,['crear','editar','eliminar','mantenimiento','habilitar'],true))throw new InvalidArgumentException('Operación inválida.');
  if($mode!=='crear'){
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
   if(!$id)throw new InvalidArgumentException('Espacio inválido.');
  }
  if($mode==='eliminar')espacios_call('sp_eliminar_espacio',[$id]);
  elseif(in_array($mode,['mantenimiento','habilitar'],true))espacios_call('sp_cambiar_estado_espacio',[$id,$mode==='habilitar'?'Libre':'Mantenimiento']);
  else{
   foreach($values as $key=>$_)$values[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
   $number=filter_var($values['numero'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>2147483647]]);
   if(!$number)throw new InvalidArgumentException('El número debe ser un entero entre 1 y 2147483647.');
   if(!in_array($values['tipo'],$types,true))throw new InvalidArgumentException('Selecciona un tipo de vehículo válido.');
   espacios_call($mode==='crear'?'sp_insertar_espacio':'sp_actualizar_espacio',$mode==='crear'?[$number,$values['tipo']]:[$id,$number,$values['tipo']]);
  }
  $_SESSION['espacio_flash']=match($mode){'crear'=>'Espacio registrado. Estado inicial: Libre.','editar'=>'Cambios guardados.','eliminar'=>'Espacio eliminado.','habilitar'=>'Espacio habilitado.','mantenimiento'=>'Espacio en mantenimiento.'};
  header('Location: espacios.php',true,303);exit;
 }
}catch(Throwable $ex){$error=espacio_error($ex);}
try{
 $rows=espacios_call('sp_listar_espacios');
 $used=array_fill_keys(array_column(espacios_call('sp_listar_ingresos'),'id_espacio'),true);
 $ready=true;
 if($_SERVER['REQUEST_METHOD']==='GET'&&isset($_GET['id'])){
  $id=filter_var($_GET['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
  $mode=in_array($_GET['accion']??'',['ver','editar','eliminar','mantenimiento','habilitar'],true)?$_GET['accion']:'ver';
 }
 if($id){
  foreach($rows as $r)if((int)$r['id_espacio']===$id){$selected=$r;break;}
  if(!$selected){$error='El espacio ya no existe.';$id=0;$mode='crear';}
  elseif($mode==='editar'&&$_SERVER['REQUEST_METHOD']==='GET')$values=['numero'=>$selected['numero'],'tipo'=>$selected['tipo']];
 }
}catch(Throwable $ex){$error=espacio_error($ex);}
$state=$selected?espacio_estado($selected):'Libre';
$canEdit=$state==='Libre';$canChange=in_array($state,['Libre','Mantenimiento'],true);
$counts=['Libre'=>0,'Ocupado'=>0,'Mantenimiento'=>0,'No reconocido'=>0];
foreach($rows as $r)$counts[espacio_estado($r)]++;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Gestión de Espacios."><title>Espacios · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="espacios"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link active" href="espacios.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Espacios</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Espacios · Base web</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Espacios</h1><p>Un lugar para cada veh&#237;culo. Todo bajo control.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<?php if($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<section class="card crud-notice" aria-label="Resumen de espacios"><?php foreach($counts as $label=>$count): ?><span class="tag"><?=e($label)?>: <?=$count?></span> <?php endforeach; ?></section>
<?php if($selected): responsable_panel('espacio',(int)$selected['id_espacio']); endif; ?>
<?php if($selected && $mode!=='editar'): ?>
<section class="card"><div class="card-head"><h2><?=e(match($mode){'eliminar'=>'Confirmar eliminación','mantenimiento'=>'Poner en mantenimiento','habilitar'=>'Habilitar espacio',default=>'Detalle del espacio'})?></h2></div><div class="crud-detail"><p><strong>Espacio:</strong> <?=e($selected['numero'])?></p><p><strong>Tipo:</strong> <?=e($selected['tipo'])?></p><p><strong>Estado:</strong> <?=e($state)?></p><p><strong>Historial:</strong> <?=isset($used[$id])?'Tiene movimientos asociados':'Sin movimientos asociados'?></p>
<?php if(in_array($mode,['eliminar','mantenimiento','habilitar'],true)):
 $allowed=$canChange&&($mode!=='eliminar'||!isset($used[$id])); ?>
<p><?=!$canChange?'Registra la salida del vehículo antes de cambiar este espacio.':($mode==='eliminar'&&isset($used[$id])?'No se permite eliminar espacios con historial. Puedes ponerlo en mantenimiento.':($mode==='eliminar'?'Se eliminará este espacio de la base web.':'Confirma el cambio de disponibilidad.'))?></p>
<?php if($allowed): ?><form method="post" action="espacios.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="<?=e($mode)?>"><button class="btn primary" type="submit">Confirmar</button></form><?php endif; endif; ?>
<a class="btn" href="espacios.php">Volver al listado</a></div></section>
<?php else: ?>
<section class="card"><div class="card-head"><h2><?=$mode==='editar'?'Editar espacio':'Crear espacio'?></h2><small>El número identifica el espacio para el usuario</small></div>
<form method="post" action="espacios.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="<?=$mode==='editar'?'editar':'crear'?>">
<div class="form-grid"><label for="space-number">Número del espacio<input type="number" name="numero" id="space-number" min="1" max="2147483647" step="1" required value="<?=e($values['numero'])?>" <?=!$canEdit?'readonly':''?>></label>
<label for="space-type">Tipo de vehículo<select name="tipo" id="space-type" required <?=!$canEdit?'disabled':''?>><option value="">Selecciona un tipo</option><?php foreach($types as $t): ?><option value="<?=e($t)?>" <?=$values['tipo']===$t?'selected':''?>><?=e($t)?></option><?php endforeach; ?></select></label></div>
<div class="form-actions"><span class="form-hint"><?=!$canEdit?'Solo se editan espacios libres. Registra la salida o habilita el espacio primero.':'Los espacios nuevos se crean libres. La ocupación se controla desde Ingresos y Salidas.'?></span><a class="btn" href="espacios.php">Cancelar / limpiar</a><button class="btn primary" type="submit" <?=!$ready||!$types||!$canEdit?'disabled':''?>><?=$mode==='editar'?'Guardar cambios':'Crear espacio'?></button></div>
<?php if(!$types): ?><p class="crud-notice">Configura primero los tipos de vehículo mediante SQL.</p><?php endif; ?></form></section><?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Espacios <span class="count" id="row-count"><?=count($rows)?></span></h2><label class="search"><input type="search" id="space-search" aria-label="Buscar espacios" placeholder="Buscar número o tipo…"></label><label>Estado<select id="space-filter"><option value="">Todos</option><?php foreach(array_keys($counts) as $label): ?><option><?=e($label)?></option><?php endforeach; ?></select></label><label>Tipo<select id="space-type-filter"><option value="">Todos</option><?php foreach($types as $t): ?><option><?=e($t)?></option><?php endforeach; ?></select></label></div>
<div class="table-wrap"><table><thead><tr><th>Espacio</th><th>Tipo</th><th>Estado</th><th>Historial</th><th>Acciones</th></tr></thead><tbody id="space-rows">
<?php foreach($rows as $r): $current=espacio_estado($r);$rid=(int)$r['id_espacio']; ?>
<tr data-status="<?=e($current)?>" data-type="<?=e($r['tipo'])?>"><td>Espacio <?=e($r['numero'])?></td><td><?=e($r['tipo'])?></td><td><?=e($current)?></td><td><?=isset($used[$rid])?'Con movimientos':'Sin movimientos'?></td><td><div class="row-actions">
<?php $actions=['ver'=>'Ver'];if($current==='Libre')$actions['editar']='Editar';if(in_array($current,['Libre','Mantenimiento'],true)){$actions[$current==='Libre'?'mantenimiento':'habilitar']=$current==='Libre'?'Mantenimiento':'Habilitar';if(!isset($used[$rid]))$actions['eliminar']='Eliminar';}
foreach($actions as $action=>$label): ?><a class="btn small" href="espacios.php?accion=<?=$action?>&amp;id=<?=$rid?>" aria-label="<?=e($label.' espacio '.$r['numero'])?>"><?=$label?></a><?php endforeach; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div><p id="space-empty" class="empty" <?=count($rows)?'hidden':''?>>No hay espacios que mostrar.</p><div class="table-foot"><span>Disponibilidad real de la base web</span><a href="espacios.php">Actualizar listado</a></div></section></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Espacios · Base compartida</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/espacios.js" defer></script></body></html>
