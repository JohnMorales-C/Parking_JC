<?php


declare(strict_types=1);
require_once __DIR__.'/includes/fechas-vista.php';
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
$authUser=auth_require(false);
require_once __DIR__.'/includes/incidentes-service.php';
function e($value):string{return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$_SESSION['csrf']??=bin2hex(random_bytes(32));$csrf=$_SESSION['csrf'];
$message=$_SESSION['incidente_flash']??'';unset($_SESSION['incidente_flash']);
$error='';$rows=[];$movements=[];$selected=null;$filter=null;$ready=false;
$values=['movimiento'=>'','tipo'=>'','descripcion'=>''];
try{
 $movements=array_column(incidentes_call('sp_listar_movimientos_incidente'),null,'id_ingreso');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!is_string($_POST['csrf']??null)||!hash_equals($csrf,$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('El formulario venció. Recarga la página.');}
  if(($_POST['action']??'')!=='crear')throw new InvalidArgumentException('Operación inválida. El historial de incidentes se conserva.');
  foreach($values as $key=>$_){
   if(!is_string($_POST[$key]??null))throw new InvalidArgumentException('Completa todos los campos.');
   $values[$key]=trim($_POST[$key]);
  }
  $movement=filter_var($values['movimiento'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if(!$movement||!isset($movements[$movement]))throw new InvalidArgumentException('Selecciona un movimiento existente.');
  if($values['tipo']===''||mb_strlen($values['tipo'],'UTF-8')>100)throw new InvalidArgumentException('El tipo de incidente debe tener entre 1 y 100 caracteres.');
  if($values['descripcion']===''||mb_strlen($values['descripcion'],'UTF-8')>5000)throw new InvalidArgumentException('La descripción debe tener entre 1 y 5000 caracteres.');
  incidentes_call('sp_insertar_incidente',[$movement,$values['tipo'],$values['descripcion']]);
  $_SESSION['incidente_flash']='Incidente registrado correctamente.';
  header('Location: incidentes.php?movimiento='.$movement,true,303);exit;
 }
 if(isset($_GET['movimiento'])&&$_GET['movimiento']!==''){
  $filter=filter_var($_GET['movimiento'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
  if(!$filter||!isset($movements[$filter]))throw new InvalidArgumentException('El movimiento ya no existe. Actualiza las opciones.');
  $values['movimiento']=(string)$filter;
 }
 $rows=incidentes_call('sp_listar_incidentes',[$filter]);$ready=true;
 if(isset($_GET['detalle'])){
  $id=filter_var($_GET['detalle'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
  foreach($rows as $row)if((int)$row['id_incidente']===$id){$selected=$row;break;}
  if(!$selected)throw new InvalidArgumentException('El incidente no está disponible en este listado.');
 }
}catch(Throwable $ex){
 $error=incidente_error($ex);
 // Mantener el listado accesible después de un error de validación, sin modificar registros.
 if($_SERVER['REQUEST_METHOD']==='POST')try{$rows=incidentes_call('sp_listar_incidentes',[null]);$ready=true;}catch(Throwable $ignored){}
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Registro y consulta de incidentes."><title>Incidentes · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><link rel="stylesheet" href="../assets/incidentes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="incidentes"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link active" href="incidentes.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Incidentes</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Incidentes · Base web</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Incidentes</h1><p>Cada novedad, vinculada a la visita correcta.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<?php if($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<?php if($selected): responsable_panel('incidente',(int)$selected['id_incidente']); endif; ?>
<?php if($selected): ?>
<section class="card"><div class="card-head"><h2>Detalle del incidente</h2></div><div class="crud-detail">
<p><strong>Movimiento:</strong> <?=e($movements[$selected['id_ingreso']]['etiqueta']??$selected['placa'])?></p>
<p><strong>Registrado:</strong> <?=e(fecha_visible($selected['fecha']))?></p><p><strong>Tipo:</strong> <?=e($selected['tipo'])?></p>
<p class="incident-description"><?=e($selected['descripcion'])?></p>
<a class="btn" href="incidentes.php<?=$filter?'?movimiento='.$filter:''?>">Volver al listado</a>
<a class="btn" href="salida.php?ingreso=<?=(int)$selected['id_ingreso']?>">Ver movimiento</a></div></section>
<?php else: ?>
<section class="card"><div class="card-head"><h2>Registrar una novedad</h2><small>Fecha automática</small></div>
<form method="post" action="incidentes.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="crear">
<div class="form-grid"><label for="incident-movement">Movimiento: placa · entrada · espacio
<select id="incident-movement" name="movimiento" required><option value="">Selecciona una visita</option>
<?php foreach($movements as $m): ?><option value="<?=(int)$m['id_ingreso']?>" <?=(string)$values['movimiento']===(string)$m['id_ingreso']?'selected':''?>><?=e($m['etiqueta'])?></option><?php endforeach; ?></select></label>
<label for="incident-type">Tipo de incidente<input id="incident-type" name="tipo" required maxlength="100" value="<?=e($values['tipo'])?>"></label>
<label for="incident-description" class="span-all">Descripción<textarea id="incident-description" name="descripcion" maxlength="5000" rows="5" required placeholder="Describe lo ocurrido."><?=e($values['descripcion'])?></textarea></label></div>
<div class="form-actions"><span class="form-hint">Puedes registrar novedades de visitas activas o terminadas. El historial se conserva.</span><a class="btn" href="incidentes.php<?=$filter?'?movimiento='.$filter:''?>">Limpiar</a><button class="btn primary" type="submit" <?=!$ready||!$movements?'disabled':''?>>Registrar incidente</button></div>
<?php if(!$movements): ?><p class="crud-notice">No hay visitas disponibles. Registra primero una entrada desde Ingresos y Salidas.</p><?php endif; ?></form></section>
<?php endif; ?>
<section class="card"><div class="card-head"><h2>Filtrar por visita</h2></div>
<form method="get" action="incidentes.php"><div class="form-grid"><label for="incident-filter">Movimiento<select name="movimiento" id="incident-filter"><option value="">Todas las visitas</option><?php foreach($movements as $m): ?><option value="<?=(int)$m['id_ingreso']?>" <?=(int)$m['id_ingreso']===$filter?'selected':''?>><?=e($m['etiqueta'])?></option><?php endforeach; ?></select></label></div><div class="form-actions"><button class="btn" type="submit">Aplicar filtro</button><a class="btn" href="incidentes.php">Ver todos</a></div></form></section>
<section class="card table-card"><div class="table-toolbar"><h2>Incidentes <span class="count" id="row-count"><?=count($rows)?></span></h2><label class="search"><input type="search" id="incident-search" aria-label="Buscar en los incidentes mostrados" placeholder="Buscar placa, día, fecha, tipo o descripción…"></label></div>
<div class="table-wrap"><table><thead><tr><th>Registro</th><th>Movimiento</th><th>Tipo</th><th>Descripción</th><th>Acciones</th></tr></thead><tbody id="incident-rows">
<?php foreach($rows as $r): ?><tr data-search="<?=e($r['fecha'].' '.$r['fecha_entrada'])?>"><td><?=e(fecha_visible($r['fecha']))?></td><td><?=e($movements[$r['id_ingreso']]['etiqueta']??$r['placa'])?></td><td><?=e($r['tipo'])?></td><td><div class="incident-summary"><?=e($r['descripcion'])?></div></td><td><a class="btn small" href="incidentes.php?detalle=<?=(int)$r['id_incidente']?><?=$filter?'&amp;movimiento='.$filter:''?>" aria-label="<?=e('Ver '.$r['tipo'].' de '.$r['placa'])?>">Ver detalle</a></td></tr><?php endforeach; ?>
</tbody></table></div><p id="incident-empty" class="empty" <?=count($rows)?'hidden':''?>>No hay incidentes que mostrar.</p><div class="table-foot"><span>Registros reales · Historial conservado</span><a href="incidentes.php<?=$filter?'?movimiento='.$filter:''?>">Actualizar</a></div></section></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Incidentes · Base compartida</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/incidentes.js?v=<?=filemtime(__DIR__.'/../assets/incidentes.js')?>" defer></script></body></html>
