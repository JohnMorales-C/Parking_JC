<?php
require_once __DIR__.'/includes/fechas-vista.php';
require_once __DIR__.'/includes/movimientos-bootstrap.php';
$vehicleId=filter_var($_GET['vehiculo']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$rows=[];$vehicles=[];$spaces=[];$responsibles=[];$allSpaces=[];$tariffs=[];$ready=false;
try {
 if($_SERVER['REQUEST_METHOD']==='POST') {
  movimiento_csrf();
  if(($_POST['action']??'')!=='entrada') throw new InvalidArgumentException('Operación inválida.');
  $vehicleId=movimiento_id($_POST['vehiculo']??null);
  $space=movimiento_id($_POST['espacio']??null);$person=$authUser['id'];
  movimiento_find(movimientos_call('sp_listar_espacios_vehiculo',[$vehicleId]),'id_espacio',$space);
  movimiento_find(movimientos_call('sp_listar_responsables'),'id_usuario',$person);
  movimientos_call('sp_registrar_ingreso',[$vehicleId,$space,$person,movimiento_now()]);
  $_SESSION['movimiento_flash']='Entrada registrada. El espacio quedó ocupado.';
  header('Location: ingresos.php',true,303);exit;
 }
} catch(Throwable $exception) {$error=movimiento_error($exception);}
try {
 $rows=movimientos_call('sp_listar_ingresos');
 $vehicles=array_column(movimientos_call('sp_listar_vehiculos'),null,'id_vehiculo');
 $allSpaces=array_column(movimientos_call('sp_listar_espacios'),null,'id_espacio');
 $responsibles=array_column(movimientos_call('sp_listar_responsables'),null,'id_usuario');
 $tariffs=array_column(movimientos_call('sp_listar_tarifas'),null,'id_tarifa');
 if($vehicleId) $spaces=movimientos_call('sp_listar_espacios_vehiculo',[$vehicleId]);
 $ready=true;
} catch(Throwable $exception) {$error=movimiento_error($exception);}
$active=array_column(array_filter($rows,fn($r)=>$r['fecha_salida']===null),'id_vehiculo');
$available=array_filter($vehicles,fn($v)=>!in_array($v['id_vehiculo'],$active));
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Gestión de entradas y salidas."><title>Ingresos y salidas · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="ingresos"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link active" href="ingresos.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Ingresos y salidas</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Movimientos · MySQL local</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Ingresos y salidas</h1><p>Una entrada ordenada. Una salida sin complicaciones.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<p class="form-hint">El responsable del ingreso se asigna automáticamente desde tu sesión.</p>
<?php if($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<section class="card"><div class="card-head"><h2>01 · Elegir vehículo</h2></div>
<form method="get" action="ingresos.php" id="vehicle-choice"><div class="form-grid"><label for="vehicle-choice-select">Placa y tipo
<select name="vehiculo" id="vehicle-choice-select" required><option value="">Selecciona una placa</option>
<?php foreach($available as $v): ?><option value="<?=(int)$v['id_vehiculo']?>" <?=(int)$v['id_vehiculo']===$vehicleId?'selected':''?>><?=e($v['placa'].' · '.$v['tipo'])?></option><?php endforeach; ?>
</select></label></div><div class="form-actions"><span class="form-hint">Solo aparecen vehículos sin entrada activa.</span><button class="btn" type="submit">Consultar espacios</button><a class="btn" href="vehiculos.php">Gestionar vehículos</a></div></form></section>
<?php if($vehicleId && isset($available[$vehicleId])): ?>
<section class="card"><div class="card-head"><h2>02 · Registrar entrada de <?=e($available[$vehicleId]['placa'])?></h2></div>
<form method="post" action="ingresos.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="entrada"><input type="hidden" name="vehiculo" value="<?=$vehicleId?>">
<div class="form-grid"><label for="entry-space">Espacio disponible<select name="espacio" id="entry-space" required><option value="">Selecciona un espacio</option>
<?php foreach($spaces as $s): ?><option value="<?=(int)$s['id_espacio']?>"><?=e($s['etiqueta'])?></option><?php endforeach; ?>
</select><?php if(!$spaces): ?><small>No hay espacios libres compatibles con este vehículo.</small><?php endif; ?></label>
<label for="entry-person">Responsable<input id="entry-person" value="<?=h($authUser['nombre'])?>" readonly></label></div>
<div class="form-actions"><span class="form-hint">La fecha y hora se asignan automáticamente al guardar.</span><button class="btn primary" type="submit" <?=!$ready||!$spaces||!$responsibles?'disabled':''?>>Registrar entrada</button></div></form></section>
<?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Movimientos <span class="count" id="row-count"><?=count($rows)?></span></h2><label class="search"><input id="movement-search" type="search" placeholder="Buscar placa, día, fecha o responsable…" aria-label="Buscar movimientos"></label><label>Mostrar<select id="movement-filter"><option value="">Todos</option><option value="activo">En parqueadero</option><option value="cerrado">Con salida</option></select></label></div>
<div class="table-wrap"><table><thead><tr><th>Placa</th><th>Espacio</th><th>Responsable</th><th>Entrada</th><th>Salida</th><th>Horas</th><th>Total</th><th>Tarifa</th><th>Método de pago</th><th>Acciones</th></tr></thead><tbody id="movement-rows">
<?php foreach($rows as $r): $s=$allSpaces[$r['id_espacio']]??null; ?>
<tr data-search="<?=e($r['fecha_entrada'].' '.($r['fecha_salida']??''))?>" data-status="<?=$r['fecha_salida']===null?'activo':'cerrado'?>">
<td><?=e($vehicles[$r['id_vehiculo']]['placa']??'Vehículo no disponible')?></td>
<td><?=e($s?'Espacio '.$s['numero'].' · '.$s['tipo']:'Espacio no disponible')?></td>
<td><?=e($responsibles[$r['id_usuario']]['etiqueta']??'Responsable no disponible')?></td>
<td><?=e(fecha_visible($r['fecha_entrada']))?></td><td><?=e($r['fecha_salida']?fecha_visible($r['fecha_salida']):'En parqueadero')?></td><td><?=e($r['tiempo_horas']??'—')?></td>
<td><?=$r['fecha_salida']!==null?money($r['total']):'—'?></td><td><?=e($tariffs[$r['id_tarifa']]['codigo_tarifa']??'—')?></td><td><?=e($r['metodo_pago']??'—')?></td>
<td><a class="btn small" href="salida.php?ingreso=<?=(int)$r['id_ingreso']?>"><?=$r['fecha_salida']===null?'Registrar salida':'Ver detalle'?></a> <a class="btn small" href="incidentes.php?movimiento=<?=(int)$r['id_ingreso']?>">Incidentes</a></td>
</tr><?php endforeach; ?></tbody></table></div><p id="movement-empty" class="empty" <?=count($rows)?'hidden':''?>>No hay movimientos que mostrar.</p><div class="table-foot"><span>Datos reales · Historial conservado</span><a href="ingresos.php">Actualizar</a></div></section></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Movimientos guardados en MySQL</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/ingresos.js?v=<?=filemtime(__DIR__.'/../assets/ingresos.js')?>" defer></script></body></html>
