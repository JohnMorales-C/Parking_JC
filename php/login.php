<?php
require __DIR__.'/includes/web-bootstrap.php';
$error='';$setup=false;
try{
 if(auth_current()){header('Location: inicio.php',true,303);exit;}
 $setup=(bool)(auth_call('sp_estado_acceso')[0]['requiere_configuracion']??false);
 if($_SERVER['REQUEST_METHOD']==='POST'){
  auth_csrf();auth_login(auth_text($_POST,'usuario',50),auth_secret($_POST,'clave'));
  header('Location: inicio.php',true,303);exit;
 }
}catch(Throwable $e){$error=auth_error($e);}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bienvenido de nuevo · Parking JC</title><link rel="stylesheet" href="../assets/styles.css"><script src="../assets/app.js" defer></script></head><body data-page="index"><main class="auth"><section class="auth-art"><a class="brand" href="login.php"><span class="brand-mark">P</span><span>Parking JC<small>GESTIÓN DE PARQUEADERO</small></span></a><div><h1>Un lugar para todo.<br><em>Todo en su lugar.</em></h1><p>Tu parqueadero organizado, desde la primera entrada hasta la última salida.</p><div class="auth-illustration" aria-hidden="true"><div class="bay car"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg></div><div class="bay">P</div><div class="bay car"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></svg></div></div></div><footer>PARKING JC &nbsp; / &nbsp; MÁS ORDEN, MEJOR SERVICIO</footer></section><section class="auth-content"><div class="auth-box"><span class="demo-tag">Acceso seguro</span><h1>Bienvenido de nuevo</h1><p>Ingresa con tu cuenta de Administrador u Operador.</p>
<?php if(isset($_GET['cambio'])): ?><p role="status">Contraseña actualizada. Inicia sesión nuevamente.</p><?php endif; ?>
<?php if($error): ?><p role="alert"><?=h($error)?></p><?php endif; ?>
<form method="post" action="login.php"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<label for="login-user">Usuario<input id="login-user" name="usuario" maxlength="50" required autocomplete="username"></label>
<label for="login-pass">Contraseña<input id="login-pass" name="clave" type="password" maxlength="128" required autocomplete="current-password"></label>
<button class="btn primary" type="submit">Iniciar sesión</button></form>
<?php if($setup): ?><div class="auth-links"><a href="configuracion.php">Configurar el primer administrador</a></div><?php endif; ?>
<p class="demo-notice">La web y la aplicación de escritorio utilizan las mismas cuentas y los mismos datos.</p>
</div></section></main></body></html>