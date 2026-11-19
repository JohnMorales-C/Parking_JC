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
$authUser=auth_require(false);
require_once __DIR__.'/includes/clientes-service.php';
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
$message = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$error = ''; $rows = []; $selected = null;
$values = ['nombre'=>'', 'documento'=>'', 'telefono'=>'', 'correo'=>''];
$mode = 'crear'; $id = 0; $connected = false;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
            http_response_code(403); throw new InvalidArgumentException('El formulario venció. Recarga la página antes de intentar de nuevo.');
        }
        $action = $_POST['action'] ?? '';
        if (!in_array($action, ['crear','editar','eliminar'], true)) throw new InvalidArgumentException('Operación inválida.');
        if ($action !== 'crear') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
            if (!$id) throw new InvalidArgumentException('Registro inválido.');
        }
        $mode = $action;
        if ($action === 'eliminar') clientes_call('sp_eliminar_cliente', [$id]);
        else {
            foreach ($values as $key=>$_) $values[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
            $values = cliente_validar($_POST);
            $params = array_values($values);
            if ($action === 'editar') array_unshift($params, $id);
            clientes_call($action === 'crear' ? 'sp_insertar_cliente' : 'sp_actualizar_cliente', $params);
        }
        $_SESSION['flash'] = ['crear'=>'Cliente registrado correctamente.', 'editar'=>'Cambios guardados.', 'eliminar'=>'Cliente eliminado.'][$action];
        header('Location: clientes.php', true, 303); exit;
    }
} catch (Throwable $exception) { $error = cliente_error($exception); }
try {
    $rows = clientes_call('sp_listar_clientes'); $connected = true;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
        $mode = in_array($_GET['accion'] ?? '', ['editar','eliminar','ver'], true) ? $_GET['accion'] : 'ver';
        foreach ($rows as $row) if ((int)$row['id_cliente'] === $id) { $selected = $row; break; }
        if (!$selected) { $error = 'El cliente ya no existe.'; $mode = 'crear'; $id = 0; }
        elseif ($mode === 'editar') foreach ($values as $key=>$_) $values[$key] = $selected[$key] ?? '';
    }
    if ($mode === 'eliminar' && !$selected) foreach ($rows as $row) if ((int)$row['id_cliente'] === $id) $selected = $row;
} catch (Throwable $exception) { $error = cliente_error($exception); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Parking JC: Gestión de clientes."><title>Clientes · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><link rel="stylesheet" href="../assets/clientes.css"><script src="../assets/app.js" defer></script></head>
<body data-page="clientes"><a class="skip" href="#contenido">Saltar al contenido</a><button class="mobile-overlay" aria-label="Cerrar menú"></button><aside class="sidebar" id="sidebar"><a class="brand" href="inicio.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div class="nav-title">Tu espacio de trabajo</div><nav aria-label="Navegación principal"><a class="nav-link" href="inicio.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/></svg><span>Inicio</span></a>
<a class="nav-link" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/></svg><span>Ingresos y salidas</span></a>
<a class="nav-link active" href="clientes.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/></svg><span>Clientes</span></a>
<a class="nav-link" href="vehiculos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg><span>Vehículos</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="espacios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Espacios</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="tarifas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg><span>Tarifas</span></a><?php endif; ?>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="pagos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg><span>Métodos de pago</span></a><?php endif; ?>
<a class="nav-link" href="incidentes.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2zM12 9v5m0 3h.01"/></svg><span>Incidentes</span></a>
<?php if($authUser['rol']==='Administrador'): ?><a class="nav-link" href="usuarios.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z"/><path d="m8 12 3 3 5-6"/></svg><span>Usuarios y roles</span></a><?php endif; ?></nav><?php auth_sidebar($authUser); ?></aside>
<div class="app"><header class="topbar"><button class="menu-toggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button><div class="breadcrumbs">Parking JC <span>/</span> Clientes</div><div class="top-actions"><span class="date"><?php echo date("d/m/Y"); ?></span><span class="demo-tag"><i class="status-dot"></i>Clientes · MySQL local</span></div></header><main class="page" id="contenido"><div class="page-head"><div><div class="eyebrow">Administración del parqueadero</div><h1>Clientes</h1><p>Personas que conf&#237;an en tu parqueadero.</p></div><a class="btn primary" href="ingresos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nueva entrada</a></div>
<div class="stack">
<p class="form-hint">Clientes, Vehículos, Ingresos y Salidas y Tarifas guardan datos en la base compartida.</p>
<?php if ($message): ?><section class="card crud-notice" role="status"><?=e($message)?></section><?php endif; ?>
<?php if ($error): ?><section class="card crud-notice" role="alert"><?=e($error)?></section><?php endif; ?>
<?php if($selected): responsable_panel('cliente',(int)$selected['id_cliente']); endif; ?>
<?php if ($selected && in_array($mode, ['ver','eliminar'], true)): ?>
<section class="card"><div class="card-head"><h2><?=$mode === 'eliminar' ? 'Confirmar eliminación' : 'Detalle del cliente'?></h2></div>
<div class="crud-detail">
<?php foreach (['nombre'=>'Nombre', 'documento'=>'Documento', 'telefono'=>'Teléfono', 'correo'=>'Correo'] as $key=>$label): ?>
<p><strong><?=e($label)?>:</strong> <?=e($selected[$key] ?: '—')?></p>
<?php endforeach; ?>
<?php if ($mode === 'eliminar'): ?>
<p>Se eliminará este cliente de la base compartida. Si tiene vehículos asociados, no se permitirá la eliminación.</p>
<form method="post" action="clientes.php"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="eliminar"><div class="form-actions"><a class="btn" href="clientes.php">Cancelar</a><button class="btn primary" type="submit">Eliminar cliente</button></div></form>
<?php else: ?><a class="btn" href="clientes.php">Volver al listado</a><?php endif; ?>
</div></section>
<?php else: ?>
<section class="card"><div class="card-head"><h2><?=$mode === 'editar' ? 'Editar cliente' : 'Datos del cliente'?></h2><small>Nombre y documento obligatorios</small></div>
<form method="post" action="clientes.php">
<input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="<?=$mode === 'editar' ? 'editar' : 'crear'?>"><input type="hidden" name="id" value="<?=$id?>">
<div class="form-grid">
<?php foreach (['nombre'=>['Nombre completo','text',80], 'documento'=>['Documento','text',20], 'telefono'=>['Teléfono','tel',20], 'correo'=>['Correo electrónico','email',80]] as $key=>$field): ?>
<label for="campo-<?=$key?>"><?=e($field[0])?><input id="campo-<?=$key?>" name="<?=$key?>" type="<?=$field[1]?>" maxlength="<?=$field[2]?>" value="<?=e($values[$key])?>" <?=in_array($key,['nombre','documento'],true) ? 'required' : ''?>></label>
<?php endforeach; ?>
</div><div class="form-actions"><span class="form-hint">Los cambios se guardan en MySQL.</span><a class="btn" href="clientes.php"><?=$mode === 'editar' ? 'Cancelar edición' : 'Limpiar'?></a><button class="btn primary" type="submit" <?=$connected ? '' : 'disabled'?>><?=$mode === 'editar' ? 'Guardar cambios' : 'Guardar cliente'?></button></div></form></section>
<?php endif; ?>
<section class="card table-card"><div class="table-toolbar"><h2>Clientes <span class="count" id="row-count"><?=count($rows)?></span></h2><label class="search"><input type="search" id="client-search" placeholder="Buscar cliente…" aria-label="Buscar clientes"></label></div>
<div class="table-wrap"><table><thead><tr><th>Cliente</th><th>Documento</th><th>Teléfono</th><th>Correo</th><th>Acciones</th></tr></thead><tbody id="client-rows">
<?php foreach ($rows as $row): ?><tr>
<?php foreach (['nombre','documento','telefono','correo'] as $key): ?><td><?=e($row[$key] ?: '—')?></td><?php endforeach; ?>
<td><div class="row-actions"><?php foreach (['ver'=>'Ver', 'editar'=>'Editar', 'eliminar'=>'Eliminar'] as $action=>$label): ?><a class="btn small" aria-label="<?=e($label.' a '.$row['nombre'])?>" href="clientes.php?accion=<?=$action?>&amp;id=<?=(int)$row['id_cliente']?>"><?=$label?></a><?php endforeach; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div><p id="client-empty" class="empty" <?=count($rows) ? 'hidden' : ''?>>No hay clientes que mostrar.</p><div class="table-foot"><span>Datos de MySQL · Cambios persistentes</span><a href="clientes.php">Actualizar listado</a></div></section></div>
<footer class="footer"><span>Parking JC · Cada visita, en orden.</span><span>Clientes conectado a MySQL local</span></footer></main></div>
<dialog id="dialog"><h2 id="dialog-title"></h2><p id="dialog-text"></p><div id="dialog-fields"></div><div class="dialog-actions"><button class="btn" id="dialog-cancel">Cancelar</button><button class="btn primary" id="dialog-confirm">Confirmar</button></div></dialog><div class="toast" role="status" hidden></div>
<script src="../assets/clientes.js" defer></script></body></html>
