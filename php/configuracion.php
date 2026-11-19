<?php
require __DIR__.'/includes/web-bootstrap.php';$error='';$available=false;
try{
 $available=(bool)(auth_call('sp_estado_acceso')[0]['requiere_configuracion']??false);
 if(!$available){header('Location: login.php',true,303);exit;}
 if($_SERVER['REQUEST_METHOD']==='POST'){
  auth_csrf();$hash=auth_hash(auth_secret($_POST,'clave'),auth_secret($_POST,'confirmar'));
  auth_call('sp_configurar_administrador',[auth_text($_POST,'nombre',80),auth_text($_POST,'usuario',50),$hash]);
  header('Location: login.php',true,303);exit;
 }
}catch(Throwable $e){$error=auth_error($e);}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bienvenido de nuevo · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><script src="../assets/app.js" defer></script></head><body data-page="index"><main class="auth"><section class="auth-art"><a class="brand" href="login.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div><h1>Un lugar para todo.<br><em>Todo en su lugar.</em></h1><p>Tu parqueadero organizado, desde la primera entrada hasta la última salida.</p><div class="auth-illustration" aria-hidden="true"><div class="bay car"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg></div><div class="bay">P</div><div class="bay car"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg></div></div></div><footer>PARKING JC &nbsp; / &nbsp; MÁS ORDEN, MEJOR SERVICIO</footer></section><section class="auth-content"><div class="auth-box"><h1>Primer administrador</h1><p>Disponible únicamente cuando no existe un administrador habilitado.</p>
<?php if($error): ?><p role="alert"><?=h($error)?></p><?php endif; ?>
<?php if($available): ?><form method="post" action="configuracion.php"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<label>Nombre<input name="nombre" maxlength="80" required autocomplete="name"></label><label>Usuario<input name="usuario" maxlength="50" required autocomplete="username"></label>
<label>Contraseña<input name="clave" type="password" minlength="12" maxlength="128" required autocomplete="new-password"></label><label>Repite la contraseña<input name="confirmar" type="password" minlength="12" maxlength="128" required autocomplete="new-password"></label><button class="btn primary" type="submit">Crear administrador</button></form><?php endif; ?><a href="login.php">Volver al acceso</a>
</div></section></main></body></html>