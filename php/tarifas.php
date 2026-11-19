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
require_once __DIR__.'/includes/tarifas-service.php';
function e($value): string {return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function moneyT($value): string {return '$ '.number_format((float)$value,2,',','.');}
$_SESSION['csrf']??=bin2hex(random_bytes(32));$csrf=$_SESSION['csrf'];
$message=$_SESSION['tarifa_flash']??'';unset($_SESSION['tarifa_flash']);
$error='';$rows=[];$types=[];$used=[];$selected=null;$mode='crear';$id=0;$ready=false;
$values=['anio'=>date('Y'),'id_tipo'=>'','tipo_cobro'=>'HORA','horas_base'=>'1','valor_base'=>'','valor_hora_adicional'=>'0','estado'=>'VIGENTE','descripcion'=>''];
try {
 $types=array_column(tarifas_call('sp_listar_tipos_vehiculo'),null,'id_tipo');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!is_string($_POST['csrf']??null)||!hash_equals($csrf,$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('El formulario venció. Recarga la página.');}
  $mode=$_POST['action']??'';
  if(!in_array($mode,['crear','editar','eliminar'],true))throw new InvalidArgumentException('Operación inválida.');
  if($mode!=='crear'){
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
   if(!$id)throw new InvalidArgumentException('Tarifa inválida.');
  }
  if($mode==='eliminar')tarifas_call('sp_eliminar_tarifa',[$id]);
  else{
   foreach($values as $key=>$_)$values[$key]=is_string($_POST[$key]??null)?$_POST[$key]:'';
   $values=tarifa_validar($_POST,$types,$mode==='crear');
   if($mode==='crear')$code=tarifa_crear($values);
   else tarifas_call('sp_actualizar_tarifa',array_merge([$id],array_values($values)));
  }
  $_SESSION['tarifa_flash']=$mode==='crear'?'Tarifa registrada. Código generado: '.$code:($mode==='editar'?'Cambios guardados.':'Tarifa eliminada.');
  header('Location: tarifas.php',true,303);exit;
 }
}catch(Throwable $exception){$error=tarifa_error($exception);}
try{
 $rows=tarifas_call('sp_listar_tarifas');
 $used=array_fill_keys(array_filter(array_column(tarifas_call('sp_listar_ingresos'),'id_tarifa')),true);
 $ready=true;
 if($_SERVER['REQUEST_METHOD']==='GET'&&isset($_GET['id'])){
  $id=filter_var($_GET['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
  $mode=in_array($_GET['accion']??'',['editar','eliminar','ver'],true)?$_GET['accion']:'ver';
 }
 if($id){
  foreach($rows as $row)if((int)$row['id_tarifa']===$id){$selected=$row;break;}
  if(!$selected){$error='La tarifa ya no existe.';$id=0;$mode='crear';}
  elseif($mode==='editar'&&$_SERVER['REQUEST_METHOD']==='GET')foreach($values as $key=>$_)$values[$key]=$selected[$key]??'';
 }
}catch(Throwable $exception){$error=tarifa_error($exception);}
$locked=isset($used[$id]);
$fields=['anio'=>['Año','number','2020','2050','1'],'id_tipo'=>['Tipo de vehículo','select'],'tipo_cobro'=>['Modalidad','select'],'horas_base'=>['Horas base','number','1','24','1'],'valor_base'=>['Precio por hora ($)','number','0','99999999.99','0.01'],'valor_hora_adicional'=>['Hora adicional ($)','number','0','99999999.99','0.01'],'estado'=>['Vigencia','select'],'descripcion'=>['Descripción','text']];
unset($fields['horas_base']);
$options=['id_tipo'=>array_column($types,'nombre','id_tipo'),'tipo_cobro'=>['HORA'=>'HORA','DIA'=>'DIA','MES'=>'MES'],'estado'=>['VIGENTE'=>'VIGENTE','CADUCADA'=>'CADUCADA']];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Gestión de tarifas."><title>Tarifas · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css?v=<?=filemtime(__DIR__.'/../assets/clientes.css')?>"><script src="../assets/app.js" defer></script></head>
<body data-page="tarifas"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link active" href="tarifas.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Tarifas</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Tarifas · Base compartida</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Tarifas</h1><p>Valores claros para cada tipo de veh&#237;culo.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<section class="card crud-notice"><p>Cada tarifa nueva queda vigente automáticamente y caduca la anterior del mismo tipo de vehículo y modalidad. Por ejemplo, crear Carro/HORA de 2027 reemplaza la anterior de 2026 al guardar. Puedes editar precios o caducar manualmente la tarifa. Los cobros anteriores conservan sus valores.</p></section>
<?php if($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<?php if($selected): responsable_panel('tarifa',(int)$selected['id_tarifa']); endif; ?>
<?php if($selected&&in_array($mode,['ver','eliminar'],true)): ?>
<section class="card"><div class="card-head"><h2><?=$mode==='eliminar'?'Confirmar eliminación':'Detalle de tarifa'?></h2><span><?=e($selected['codigo_tarifa'])?></span></div><div class="crud-detail">
<?php foreach($fields as $key=>$field): if($selected['tipo_cobro']==='HORA'&&$key==='valor_hora_adicional')continue; ?><p><strong><?=e($key==='valor_base'?match($selected['tipo_cobro']){'DIA'=>'Precio por día, 24 horas ($)','MES'=>'Precio por mes, 30 días ($)',default=>'Precio por hora ($)'}:$field[0])?>:</strong> <?=e($key==='id_tipo'?($types[$selected[$key]]['nombre']??'No disponible'):($selected[$key]??'—'))?></p><?php endforeach; ?>
<?php if($mode==='eliminar'): ?>
<p><?=$locked?'Esta tarifa tiene cobros asociados y no se puede eliminar. Puedes cambiar su vigencia.':'Esta acción elimina la tarifa de la base compartida. Confirma para continuar.'?></p>
<?php if(!$locked): ?><form method="post" action="tarifas.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="eliminar"><input type="hidden" name="id" value="<?=$id?>"><button class="btn primary" type="submit">Eliminar tarifa</button></form><?php endif; ?>
<?php endif; ?><a class="btn" href="tarifas.php">Volver al listado</a></div></section>
<?php else: ?>
<section class="card"><div class="card-head"><h2><?=$mode==='editar'?'Editar tarifa':'Registrar tarifa'?></h2><small><?=$selected?e($selected['codigo_tarifa']):'Código automático'?></small></div>
<form method="post" action="tarifas.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="<?=$mode==='editar'?'editar':'crear'?>"><input type="hidden" name="id" value="<?=$id?>">
<?php if($locked): ?><p class="crud-notice">Puedes actualizar precios, descripción o vigencia. Los cobros anteriores conservan sus valores históricos; año, vehículo y modalidad permanecen protegidos.</p><?php endif; ?>
<div class="form-grid">
<?php foreach($fields as $key=>$field): $readonly=$locked&&in_array($key,['anio','id_tipo','tipo_cobro'],true); ?>
<label for="rate-<?=$key?>" <?=$key==='valor_hora_adicional'?'class="rate-period-extra"'.($values['tipo_cobro']==='HORA'?' hidden':''):''?>><span <?=$key==='valor_base'?'id="rate-price-label"':''?>><?=e($key==='valor_base'?match($values['tipo_cobro']){'DIA'=>'Precio por día, 24 horas ($)','MES'=>'Precio por mes, 30 días ($)',default=>$field[0]}:$field[0])?></span>
<?php if($key==='estado'&&$mode!=='editar'): ?><input id="rate-estado" value="VIGENTE (automática)" readonly><?php elseif($field[1]==='select'): ?>
<select name="<?=$key?>" id="rate-<?=$key?>" required <?=$readonly?'disabled':''?>><option value="">Selecciona una opción</option>
<?php foreach($options[$key] as $value=>$label): ?><option value="<?=e($value)?>" <?=(string)$values[$key]===(string)$value?'selected':''?>><?=e($label)?></option><?php endforeach; ?></select>
<?php if($readonly): ?><input type="hidden" name="<?=$key?>" value="<?=e($values[$key])?>"><?php endif; ?>
<?php else: ?>
<input name="<?=$key?>" id="rate-<?=$key?>" type="<?=$field[1]?>" value="<?=e($values[$key])?>" <?=$readonly?'readonly':''?> <?=$key==='valor_hora_adicional'&&$values['tipo_cobro']==='HORA'?'disabled':''?> <?=$key==='descripcion'?'maxlength="150"':'required'?> <?php if($field[1]==='number'): ?>min="<?=$field[2]?>" max="<?=$field[3]?>" step="<?=$field[4]?>"<?php endif; ?>>
<?php endif; ?></label>
<?php endforeach; ?></div><div class="form-actions"><span class="form-hint">HORA: precio uniforme. DÍA incluye 24 horas; MES incluye 30 días desde el ingreso. Después se cobra la hora adicional proporcional por minutos. Un adicional de 0 no suma cargos.</span><a class="btn" href="tarifas.php"><?=$mode==='editar'?'Cancelar':'Limpiar'?></a><button class="btn primary" type="submit" <?=!$ready||!$types?'disabled':''?>><?=$mode==='editar'?'Guardar cambios':'Guardar tarifa'?></button></div>
<?php if(!$types): ?><p class="crud-notice">No hay tipos de vehículo disponibles. Configura el catálogo por SQL.</p><?php endif; ?>
</form></section><?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Tarifas <span class="count" id="row-count"><?=count($rows)?></span></h2><label class="search"><input id="rate-search" type="search" placeholder="Buscar tarifa…" aria-label="Buscar tarifas"></label><label>Vigencia<select id="rate-filter"><option value="">Todas</option><option>VIGENTE</option><option>CADUCADA</option></select></label></div>
<div class="table-wrap"><table><thead><tr><th>Código</th><th>Año</th><th>Vehículo</th><th>Cobro</th><th>Precio</th><th>Hora adicional</th><th>Vigencia</th><th>Descripción</th><th>Acciones</th></tr></thead><tbody id="rate-rows">
<?php foreach($rows as $r): ?><tr data-status="<?=e($r['estado'])?>"><td><?=e($r['codigo_tarifa'])?></td><td><?=e($r['anio'])?></td><td><?=e($types[$r['id_tipo']]['nombre']??'No disponible')?></td><td><?=e($r['tipo_cobro'])?></td><td><?=moneyT($r['valor_base'])?></td><td><?=$r['tipo_cobro']==='HORA'?'—':moneyT($r['valor_hora_adicional'])?></td><td><?=e($r['estado'])?></td><td><?=e($r['descripcion'])?></td>
<td><div class="row-actions"><?php foreach(['ver'=>'Ver','editar'=>'Editar','eliminar'=>'Eliminar'] as $action=>$label): if($action==='eliminar'&&isset($used[$r['id_tarifa']]))continue; ?><a class="btn small" href="tarifas.php?accion=<?=$action?>&amp;id=<?=(int)$r['id_tarifa']?>" aria-label="<?=e($label.' '.$r['codigo_tarifa'])?>"><?=$label?></a><?php endforeach; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div><p id="rate-empty" class="empty" <?=count($rows)?'hidden':''?>>No hay tarifas que mostrar.</p><div class="table-foot"><span>Datos reales · Base compartida</span><a href="tarifas.php">Actualizar</a></div></section></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Tarifas · Base compartida</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/tarifas.js?v=<?=filemtime(__DIR__.'/../assets/tarifas.js')?>" defer></script></body></html>
