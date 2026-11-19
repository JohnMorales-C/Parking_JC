<?php
require_once __DIR__.'/includes/fechas-vista.php';
require_once __DIR__.'/includes/responsables-service.php';
require_once __DIR__.'/includes/movimientos-bootstrap.php';
$entry=null;$vehicle=null;$space=null;$person=null;$rates=[];$methods=[];$quote=null;$savedRate=null;
$entryId=filter_var($_GET['ingreso']??$_POST['ingreso']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
try {
 if(!$entryId) throw new InvalidArgumentException('Selecciona un movimiento desde Ingresos y Salidas.');
 $entry=movimiento_find(movimientos_call('sp_obtener_ingreso',[$entryId]),'id_ingreso',$entryId);
 $vehicle=movimiento_find(movimientos_call('sp_listar_vehiculos'),'id_vehiculo',(int)$entry['id_vehiculo']);
 $space=movimiento_find(movimientos_call('sp_listar_espacios'),'id_espacio',(int)$entry['id_espacio']);
 $people=array_column(movimientos_call('sp_listar_responsables'),null,'id_usuario');$person=$people[$entry['id_usuario']]['etiqueta']??'No disponible';
 $rates=movimientos_call('sp_listar_tarifas_salida',[$entryId]);
 $methods=movimientos_call('sp_listar_metodos_activos');
 if($entry['fecha_salida']!==null) {
  $savedRate=['codigo_tarifa'=>$entry['historico_codigo']??'Histórico no disponible'];
 }
 if($_SERVER['REQUEST_METHOD']==='POST') {
  movimiento_csrf();
  if($entry['fecha_salida']!==null) throw new InvalidArgumentException('Este movimiento ya tiene salida.');
  $action=$_POST['action']??'';
  // El presupuesto vive en la sesión, nunca se confía en un total enviado por el navegador.
  if($action==='cotizar') {
   $rate=movimiento_find($rates,'id_tarifa',movimiento_id($_POST['tarifa']??null));
   $method=movimiento_find($methods,'id_metodo',movimiento_id($_POST['metodo']??null));
   $exit=movimiento_now();$estimate=movimiento_total($entry,$rate,$exit);
   $nonce=bin2hex(random_bytes(24));
   foreach($_SESSION['salidas']??[] as $key=>$old) if($old['expires']<time()) unset($_SESSION['salidas'][$key]);
   if(count($_SESSION['salidas']??[])>=20) array_shift($_SESSION['salidas']);
   $quote=['ingreso'=>$entryId,'tarifa'=>(int)$rate['id_tarifa'],'metodo'=>(int)$method['id_metodo'],'fecha'=>$exit,'expires'=>time()+120,'total'=>$estimate['total'],'horas'=>$estimate['horas'],'codigo'=>$rate['codigo_tarifa'],'pago'=>$method['nombre'],'nonce'=>$nonce,'rate'=>$rate];
   $_SESSION['salidas'][$nonce]=$quote;
  } elseif($action==='confirmar') {
   $nonce=is_string($_POST['confirmacion']??null)?$_POST['confirmacion']:'';
   $stored=$_SESSION['salidas'][$nonce]??null;
   if(!$stored || $stored['ingreso']!==$entryId || $stored['expires']<time()) throw new InvalidArgumentException('La confirmación venció. Calcula nuevamente el cobro.');
   $rate=movimiento_find($rates,'id_tarifa',$stored['tarifa']);
   movimiento_find($methods,'id_metodo',$stored['metodo']);
   foreach(['valor_base','valor_hora_adicional','tipo_cobro'] as $key) if($rate[$key]!=$stored['rate'][$key]) throw new InvalidArgumentException('La tarifa cambió. Calcula nuevamente el cobro.');
   movimientos_call('sp_registrar_salida_cotizada',[$entryId,$stored['fecha'],$stored['tarifa'],$stored['metodo'],number_format($stored['total'],2,'.','')]);
   unset($_SESSION['salidas'][$nonce]);
   $_SESSION['movimiento_flash']='Salida registrada. El pago quedó guardado y el espacio se liberó.';
   header('Location: salida.php?ingreso='.$entryId,true,303);exit;
  } else throw new InvalidArgumentException('Operación inválida.');
 }
} catch(Throwable $exception) {$error=movimiento_error($exception);}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Gestión de entradas y salidas."><title>Salida · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="ingresos"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link active" href="ingresos.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Ingresos y salidas</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Movimientos · MySQL local</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Registrar salida</h1><p>Revisa la tarifa y el pago antes de confirmar.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<?php if($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<?php if($entry): responsable_panel('ingreso',(int)$entryId); endif; ?>
<?php if($entry && $vehicle && $space): ?>
<section class="card"><div class="card-head"><h2><?=e($entry['historico_placa']??$vehicle['placa'])?> · <?=e($entry['historico_vehiculo']??$vehicle['tipo'])?></h2><span class="tag"><?=$entry['fecha_salida']===null?'En parqueadero':'Salida registrada'?></span></div><div class="crud-detail">
<p><strong>Cliente:</strong> <?=e($vehicle['cliente']??'No disponible')?></p>
<p><strong>Espacio:</strong> <?=e('Espacio '.$space['numero'].' · '.$space['tipo'])?></p>
<p><strong>Responsable del ingreso:</strong> <?=e($person)?></p><p><strong>Entrada:</strong> <?=e(fecha_visible($entry['fecha_entrada']))?></p>
<?php if($entry['fecha_salida']!==null): ?>
<p><strong>Salida:</strong> <?=e(fecha_visible($entry['fecha_salida']))?></p><p><strong>Horas:</strong> <?=e($entry['tiempo_horas'])?></p><p><strong>Tarifa:</strong> <?=e($savedRate['codigo_tarifa']??'No disponible')?></p><p><strong>Método de pago:</strong> <?=e($entry['metodo_pago'])?></p><h2>Total guardado: <?=money($entry['total'])?></h2>
<?php if($entry['historico_codigo']??null): ?>
<p><strong>Modalidad aplicada:</strong> <?=e($entry['historico_tipo'])?></p><p><strong>Año de la tarifa:</strong> <?=e($entry['historico_anio'])?></p>
<?php if(($entry['historico_regla']??'ANTERIOR')==='POR_HORA'): ?>
<p><strong>Precio por hora aplicado:</strong> <?=money($entry['historico_valor_base'])?></p><p>Cobro uniforme proporcional a los minutos.</p>
<?php elseif($entry['historico_tipo']==='HORA'): ?>
<p>Regla anterior: bloque inicial y horas adicionales.</p><p><strong>Horas incluidas:</strong> <?=e($entry['historico_horas_base'])?></p><p><strong>Precio del bloque inicial:</strong> <?=money($entry['historico_valor_base'])?></p><p><strong>Hora adicional aplicada:</strong> <?=money($entry['historico_adicional'])?></p>
<?php elseif(in_array($entry['historico_regla']??'', ['DIA24_EXTRA','MES30_EXTRA'],true)): $included=$entry['historico_regla']==='DIA24_EXTRA'?1440:43200; ?>
<p><strong>Período incluido:</strong> <?=$included===1440?'24 horas':'30 días'?> desde el ingreso</p><p><strong>Precio del período:</strong> <?=money($entry['historico_valor_base'])?></p><p><strong>Hora adicional aplicada:</strong> <?=money($entry['historico_adicional'])?></p><p><strong>Minutos adicionales:</strong> <?=e(max(0,(int)$entry['historico_minutos']-$included))?></p><p>El excedente se cobró proporcionalmente por minutos.</p>
<?php else: ?><p><strong>Precio fijo aplicado:</strong> <?=money($entry['historico_valor_base'])?></p><?php endif; ?>
<p><strong>Minutos cobrados:</strong> <?=e($entry['historico_minutos'])?></p><p><strong>Registró la salida:</strong> <?=e($entry['historico_usuario'])?></p>
<p>Valores históricos guardados al confirmar el cobro. No se recalculan con las tarifas actuales.</p>
<?php endif; ?>
<?php endif; ?></div></section>
<?php if($entry['fecha_salida']===null && !$quote): ?>
<section class="card"><div class="card-head"><h2>Calcular cobro</h2></div>
<form method="post" action="salida.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="ingreso" value="<?=$entryId?>"><input type="hidden" name="action" value="cotizar"><div class="form-grid">
<label for="exit-tariff">Tarifa vigente<select name="tarifa" id="exit-tariff" required><option value="">Selecciona una tarifa</option>
<?php foreach($rates as $t): ?><option value="<?=(int)$t['id_tarifa']?>"><?=e($t['codigo_tarifa'].' · '.$t['tipo_cobro'].' · Base '.money($t['valor_base']).($t['tipo_cobro']==='HORA'?' / '.$t['horas_base'].' h · Adicional '.money($t['valor_hora_adicional']).'/h':''))?></option><?php endforeach; ?>
</select><?php if(!$rates): ?><small>No hay tarifas vigentes compatibles. Registra una en el módulo Tarifas de esta web.</small><?php endif; ?></label>
<label for="exit-payment">Método de pago<select name="metodo" id="exit-payment" required><option value="">Selecciona un método</option>
<?php foreach($methods as $m): ?><option value="<?=(int)$m['id_metodo']?>"><?=e($m['nombre'])?></option><?php endforeach; ?>
</select><?php if(!$methods): ?><small>No hay métodos de pago activos.</small><?php endif; ?></label></div>
<div class="form-actions"><span class="form-hint">Horas adicionales proporcionales por minutos completos. Día y mes: valor fijo.</span><button class="btn primary" type="submit" <?=!$rates||!$methods?'disabled':''?>>Calcular y revisar</button></div></form></section>
<?php elseif($quote): ?>
<section class="card"><div class="card-head"><h2>Confirmar salida</h2></div><div class="crud-detail">
<p><strong>Placa:</strong> <?=e($vehicle['placa'])?></p><p><strong>Hora de salida:</strong> <?=e($quote['fecha'])?></p><p><strong>Tiempo:</strong> <?=e($quote['horas'])?> horas</p><p><strong>Tarifa:</strong> <?=e($quote['codigo'])?></p><p><strong>Método:</strong> <?=e($quote['pago'])?></p><h2>Total estimado: <?=money($quote['total'])?></h2><p>Confirma en los próximos 2 minutos. MySQL calcula y guarda el total definitivo al registrar la salida.</p>
<form method="post" action="salida.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="ingreso" value="<?=$entryId?>"><input type="hidden" name="action" value="confirmar"><input type="hidden" name="confirmacion" value="<?=e($quote['nonce'])?>"><div class="form-actions"><a class="btn" href="salida.php?ingreso=<?=$entryId?>">Volver a calcular</a><button class="btn primary" type="submit">Confirmar salida y pago</button></div></form>
</div></section>
<?php endif; endif; ?>
<a class="btn" href="ingresos.php">Volver a Ingresos y Salidas</a></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Movimientos guardados en MySQL</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/ingresos.js?v=<?=filemtime(__DIR__.'/../assets/ingresos.js')?>" defer></script></body></html>
