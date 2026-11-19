<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function auth_call(string $name,array $params=[]):array{
 if(!in_array($name,['sp_login_usuario','sp_estado_acceso','sp_configurar_administrador','sp_listar_usuarios','sp_listar_roles_acceso','sp_guardar_usuario','sp_cambiar_clave','sp_exigir_administrador'],true))throw new InvalidArgumentException('Operación inválida.');
 $s=db()->prepare('CALL '.$name.'('.implode(',',array_fill(0,count($params),'?')).')');
 try{$s->execute($params);return $s->columnCount()?$s->fetchAll():[];}finally{$s->closeCursor();}
}
function h($value):string{return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function auth_verify(string $password,string $encoded):bool{
 if(mb_strlen($password,'UTF-8')>128)return false;
 $p=explode('$',$encoded);if(count($p)!==4||$p[0]!=='PBKDF2'||$p[1]!=='600000')return false;
 $salt=base64_decode($p[2],true);$hash=base64_decode($p[3],true);
 return $salt!==false&&$hash!==false&&strlen($salt)===16&&strlen($hash)===32&&hash_equals($hash,hash_pbkdf2('sha256',$password,$salt,600000,32,true));
}
function auth_hash(string $password,string $confirm):string{
 if($password!==$confirm)throw new InvalidArgumentException('Las contraseñas no coinciden.');
 $length=mb_strlen($password,'UTF-8');
 if($length<12||$length>128)throw new InvalidArgumentException('La contraseña debe tener entre 12 y 128 caracteres.');
 $salt=random_bytes(16);return 'PBKDF2$600000$'.base64_encode($salt).'$'.base64_encode(hash_pbkdf2('sha256',$password,$salt,600000,32,true));
}
function auth_text(array $data,string $key,int $max,bool $required=true):string{
 if(isset($data[$key])&&!is_string($data[$key]))throw new InvalidArgumentException('Datos inválidos.');
 $v=trim($data[$key]??'');
 if(($required&&$v==='')||mb_strlen($v,'UTF-8')>$max)throw new InvalidArgumentException('Revisa el campo '.$key.' (máximo '.$max.' caracteres).');
 return $v;
}
function auth_secret(array $data,string $key):string{
 if(!is_string($data[$key]??null))throw new InvalidArgumentException('Completa los campos de contraseña.');
 return $data[$key];
}
function auth_csrf():void{
 if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf']??'',$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('El formulario venció. Recarga la página.');}
}
function auth_login(string $username,string $password):void{
 unset($_SESSION['auth'],$_SESSION['pagos_auth']);
 if(($_SESSION['auth_wait']??0)>time())throw new InvalidArgumentException('Espera 30 segundos antes de volver a intentar.');
 $row=auth_call('sp_login_usuario',[$username])[0]??null;
 $dummy='PBKDF2$600000$'.base64_encode(str_repeat('x',16)).'$'.base64_encode(str_repeat('x',32));
 $valid=auth_verify($password,$row['contrasena']??$dummy);
 if(!$valid||!$row||!$row['clave_configurada']||!in_array($row['rol'],['Administrador','Operador'],true)){
  $_SESSION['auth_fail']=($_SESSION['auth_fail']??0)+1;
  if($_SESSION['auth_fail']>=5){$_SESSION['auth_wait']=time()+30;$_SESSION['auth_fail']=0;}
  throw new InvalidArgumentException('Usuario o contraseña incorrectos, o cuenta sin acceso.');
 }
 session_regenerate_id(true);
 $_SESSION['auth']=['id'=>(int)$row['id_usuario'],'usuario'=>$row['usuario'],'nombre'=>$row['nombre'],'rol'=>$row['rol'],'fingerprint'=>hash('sha256',$row['contrasena']),'seen'=>time()];
 $_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['auth_fail']=0;
 unset($_SESSION['auth_wait'],$_SESSION['salidas']);
}
function auth_current():?array{
 $auth=$_SESSION['auth']??null;if(!$auth)return null;
 if(time()-$auth['seen']>1800){unset($_SESSION['auth'],$_SESSION['salidas']);return null;}
 $row=auth_call('sp_login_usuario',[$auth['usuario']])[0]??null;
 if(!$row||(int)$row['id_usuario']!==$auth['id']||$row['rol']!==$auth['rol']||!in_array($row['rol'],['Administrador','Operador'],true)||!$row['clave_configurada']||!hash_equals($auth['fingerprint'],hash('sha256',$row['contrasena']))){
  unset($_SESSION['auth'],$_SESSION['salidas']);return null;
 }
 if(($_SERVER['HTTP_X_PARKING_REFRESH']??'')!=='1')$_SESSION['auth']['seen']=time();$_SESSION['auth']['nombre']=$row['nombre'];return $_SESSION['auth'];
}
function auth_require(bool $admin=false):array{
 try{$user=auth_current();}catch(Throwable $e){http_response_code(503);exit('No se pudo comprobar la sesión. Revisa MySQL y recarga.');}
 if(!$user){header('Location: /Parqueadero_jc_web/php/login.php',true,303);exit;}
 if($admin&&$user['rol']!=='Administrador'){http_response_code(403);exit('<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso restringido</title><p>Esta sección requiere el rol Administrador.</p><a href="inicio.php">Volver al inicio</a></html>');}
 return $user;
}
function auth_logout():void{
 $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
}
function auth_error(Throwable $e):string{
 if($e instanceof InvalidArgumentException)return $e->getMessage();
 if($e instanceof PDOException){
  if((int)($e->errorInfo[1]??0)===1062)return 'Ya existe una cuenta con ese usuario.';
  $text=$e->errorInfo[2]??'';
  if((int)($e->errorInfo[1]??0)===1644&&in_array($text,['Esta accion requiere el rol Administrador','Revisa los datos del usuario','Selecciona un rol de acceso valido','La cuenta nueva necesita una clave','El usuario ya no existe','Configura una nueva clave para habilitar esta cuenta','Debe permanecer al menos un administrador con acceso','La cuenta cambio. Vuelve a iniciar sesion','El administrador inicial ya esta configurado. Inicia sesion','Configuracion en curso. Intenta nuevamente','Revisa nombre, usuario y clave protegida'],true))return $text;
 }
 return 'No se pudo completar la operación. Revisa MySQL y vuelve a intentarlo.';
}
function auth_sidebar(array $u):void{
 echo '<script src="../assets/actualizacion.js" defer></script>';
 if($u['rol']==='Administrador')echo '<a class="nav-link" href="auditoria.php">Auditoría de operaciones</a>';
 echo '<div class="sidebar-bottom"><div class="profile"><span class="avatar">'.h(mb_substr($u['nombre'],0,1,'UTF-8')).'</span><div><strong>'.h($u['nombre']).'</strong><small>'.h($u['rol']).'</small></div></div><a class="subtle-link" href="clave.php">Cambiar mi contraseña</a><form method="post" action="logout.php"><input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'"><button class="subtle-link" type="submit">Cerrar sesión</button></form></div>';
}
$_SESSION['csrf']??=bin2hex(random_bytes(32));
