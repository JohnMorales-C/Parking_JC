<?php
require __DIR__.'/includes/web-bootstrap.php';
$authUser=auth_require(true);$rows=[];$error='';
$tables=['cobro','cliente','vehiculo','espacio','tarifa','ingreso','incidente','metodo_pago','usuario','marca','color','rol','tipo_vehiculo','estado_vehiculo'];
$filter=$_GET['tabla']??'';
try{
 if(!is_string($filter)||($filter!==''&&!in_array($filter,$tables,true)))throw new InvalidArgumentException('Selecciona una tabla válida.');
 $s=db()->prepare('CALL sp_listar_auditoria(?,?)');
 try{$s->execute([$authUser['id'],$filter]);$rows=$s->fetchAll();}finally{$s->closeCursor();}
}catch(Throwable $e){$error=auth_error($e);}
function audit_json($value):string{if($value===null)return 'Sin datos';$decoded=json_decode($value,true);return json_encode($decoded,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?:'Sin datos';}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Auditoría · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><link rel="stylesheet" href="../assets/auditoria.css"><script src="../assets/app.js" defer></script></head>
<body><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button>
<aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC</span></a><nav aria-label="Navegación"><a class="nav-link" href="inicio.php">Inicio</a><a class="nav-link" href="usuarios.php">Usuarios y roles</a><a class="nav-link" href="ingresos.php">Ingresos y salidas</a></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">Menú</button><div class="breadcrumbs">Parking JC / Auditoría</div></header>
<main class="page" id="contenido"><div class="page-head"><div><h1>Auditoría de operaciones</h1><p>Quién realizó cada cambio, cuándo y desde qué aplicación.</p></div><a class="btn" href="inicio.php">Volver al inicio</a></div>
<div class="stack"><section class="card"><div class="card-head"><h2>Historial de solo lectura</h2></div><p>Se muestran hasta 300 operaciones recientes. Las operaciones de SQL directo se identifican como mantenimiento. No se registran contraseñas ni sus hashes.</p>
<form method="get" action="auditoria.php"><label for="audit-table">Tabla<select id="audit-table" name="tabla"><option value="">Todas</option><?php foreach($tables as $table): ?><option value="<?=h($table)?>" <?=is_string($filter)&&$filter===$table?'selected':''?>><?=h($table)?></option><?php endforeach; ?></select></label><div class="form-actions"><button class="btn primary" type="submit">Consultar</button><a class="btn" href="auditoria.php">Actualizar todas</a></div></form></section>
<?php if($error): ?><p class="crud-notice" role="alert"><?=h($error)?></p><?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Operaciones <span class="count"><?=count($rows)?></span></h2></div><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Perfil</th><th>Origen</th><th>Acción</th><th>Tabla</th><th>Registro</th><th>Cambios</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?=h($row['fecha'])?></td><td><?=h($row['usuario'])?><br><small><?=h($row['nombre_usuario'])?></small></td><td><?=h($row['rol_usuario'])?></td><td><?=h($row['origen'])?></td><td><?=h($row['accion'])?></td><td><?=h($row['tabla'])?></td><td><?=h($row['id_registro'])?></td><td><details><summary>Ver cambios</summary><strong>Antes</strong><pre class="audit-changes"><?=h(audit_json($row['antes']))?></pre><strong>Después</strong><pre class="audit-changes"><?=h(audit_json($row['despues']))?></pre></details></td></tr><?php endforeach; ?>
</tbody></table></div><?php if(!$rows): ?><p class="empty">Todavía no hay operaciones para este filtro. Los registros se generan a partir de la instalación de la auditoría.</p><?php endif; ?></section></div></main></div></body></html>
