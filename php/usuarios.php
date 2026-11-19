<?php
require_once __DIR__.'/includes/responsables-service.php';
require __DIR__.'/includes/web-bootstrap.php';$authUser=auth_require(true);
$error='';$message=$_SESSION['usuarios_flash']??'';unset($_SESSION['usuarios_flash']);$rows=[];$roles=[];$id=0;$ready=false;
$values=['nombre'=>'','usuario'=>'','rol'=>''];
try{
 $roles=array_column(auth_call('sp_listar_roles_acceso',[$authUser['id']]),'nombre','id_rol');
 $roles[0]='Sin acceso';
 if($_SERVER['REQUEST_METHOD']==='POST'){
  auth_csrf();
  if(($_POST['action']??'')!=='guardar')throw new InvalidArgumentException('Operación inválida.');
  $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
  if($id===false)throw new InvalidArgumentException('Cuenta inválida.');
  $values=['nombre'=>auth_text($_POST,'nombre',80),'usuario'=>auth_text($_POST,'usuario',50),'rol'=>auth_text($_POST,'rol',12)];
  $role=filter_var($values['rol'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
  if($role===false||!array_key_exists($role,$roles))throw new InvalidArgumentException('Selecciona un rol válido.');
  $password=auth_secret($_POST,'clave');$confirm=auth_secret($_POST,'confirmar');
  $hash=($id===0||$password!==''||$confirm!=='')?auth_hash($password,$confirm):null;
  auth_call('sp_guardar_usuario',[$authUser['id'],$id,$values['nombre'],$values['usuario'],$role,$hash]);
  $_SESSION['usuarios_flash']='Cuenta guardada correctamente.';header('Location: usuarios.php',true,303);exit;
 }
}catch(Throwable $e){$error=auth_error($e);}
try{
 $rows=auth_call('sp_listar_usuarios',[$authUser['id']]);$ready=true;
 if($_SERVER['REQUEST_METHOD']==='GET'&&(isset($_GET['editar'])||isset($_GET['ver']))){
  $id=filter_var($_GET['editar']??$_GET['ver'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;$selected=null;
  foreach($rows as $r)if((int)$r['id_usuario']===$id)$selected=$r;
  if(!$selected){$id=0;throw new InvalidArgumentException('La cuenta ya no existe.');}
  $values=['nombre'=>$selected['nombre'],'usuario'=>$selected['usuario'],'rol'=>(string)($selected['id_rol']??0)];
 }
}catch(Throwable $e){$error=auth_error($e);}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Usuarios y roles."><title>Usuarios y roles · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="clientes"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link" href="clientes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link active" aria-current="page" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Usuarios y roles</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Base compartida</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Usuarios y roles</h1><p>Tu parqueadero, organizado.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<?php if($message): ?><section class="card crud-notice" role="status"><?=h($message)?></section><?php endif; ?>
<?php if($error): ?><section class="card crud-notice" role="alert"><?=h($error)?></section><?php endif; ?>
<?php if(isset($_GET['ver'])&&isset($selected)&&$selected): ?><section class="card"><div class="card-head"><h2>Detalle del usuario</h2></div><div class="crud-detail"><p><strong>Nombre:</strong> <?=h($selected['nombre'])?></p><p><strong>Usuario:</strong> <?=h($selected['usuario'])?></p><p><strong>Perfil:</strong> <?=h($selected['rol'])?></p></div></section><?php endif; ?>
<?php if($id>0): responsable_panel('usuario',(int)$id); endif; ?>
<?php if(!isset($_GET['ver'])): ?><section class="card"><div class="card-head"><h2><?=$id?'Editar cuenta':'Crear cuenta'?></h2></div><form method="post" action="usuarios.php"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="guardar"><input type="hidden" name="id" value="<?=$id?>">
<div class="form-grid"><label>Nombre<input name="nombre" maxlength="80" required value="<?=h($values['nombre'])?>"></label><label>Usuario<input name="usuario" maxlength="50" required value="<?=h($values['usuario'])?>" autocomplete="off"></label>
<label>Rol<select name="rol" required><option value="">Selecciona un rol</option><?php foreach($roles as $rid=>$label): ?><option value="<?=$rid?>" <?=(string)$rid===(string)$values['rol']?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select></label>
<label><?=$id?'Nueva contraseña (opcional)':'Contraseña'?><input name="clave" type="password" minlength="12" maxlength="128" <?=$id?'':'required'?> autocomplete="new-password"></label><label>Repite la contraseña<input name="confirmar" type="password" minlength="12" maxlength="128" <?=$id?'':'required'?> autocomplete="new-password"></label></div>
<div class="form-actions"><span class="form-hint">Sin acceso deshabilita la cuenta y conserva su historial. Debe permanecer un administrador habilitado.</span><a class="btn" href="usuarios.php">Cancelar / limpiar</a><button class="btn primary" <?=$ready?'':'disabled'?>>Guardar cuenta</button></div></form></section><?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Usuarios</h2><label class="search"><input id="user-search" type="search" placeholder="Buscar usuario…" aria-label="Buscar usuarios"></label></div><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Acceso</th><th>Acciones</th></tr></thead><tbody id="user-rows">
<?php foreach($rows as $r): ?><tr><td><?=h($r['nombre'])?></td><td><?=h($r['usuario'])?></td><td><?=h($r['rol'])?></td><td><?=$r['clave_configurada']&&in_array($r['rol'],['Administrador','Operador'],true)?'Habilitado':'Sin acceso'?></td><td><a class="btn small" href="usuarios.php?ver=<?=(int)$r['id_usuario']?>">Ver</a> <a class="btn small" href="usuarios.php?editar=<?=(int)$r['id_usuario']?>">Editar / cambiar acceso</a></td></tr><?php endforeach; ?>
</tbody></table></div><p id="user-empty" class="empty" <?=count($rows)?'hidden':''?>>No hay usuarios que mostrar.</p></section></div><script src="../assets/usuarios.js" defer></script>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Base compartida</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
</body></html>
