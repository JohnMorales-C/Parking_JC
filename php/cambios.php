<?php
declare(strict_types=1);
require __DIR__.'/includes/web-bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try {
 $seen=$_SESSION['auth']['seen']??null;
 $user=auth_current();
 // Una comprobación automática no equivale a actividad de la persona.
 if($user&&$seen!==null)$_SESSION['auth']['seen']=$seen;
 if(!$user){http_response_code(401);echo json_encode(['error'=>'sesion']);exit;}
 $s=db()->prepare('CALL sp_version_datos(?)');
 try{$s->execute([$user['id']]);$row=$s->fetch(PDO::FETCH_ASSOC);}finally{$s->closeCursor();}
 session_write_close();
 echo json_encode(['version'=>$row['version']],JSON_THROW_ON_ERROR);
}catch(Throwable $e){error_log('Actualizacion: '.$e->getMessage());http_response_code(503);echo json_encode(['error'=>'conexion']);}
