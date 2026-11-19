<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function pagos_call(string $name,array $params=[]):array{
 if(!in_array($name,['sp_login_usuario','sp_exigir_administrador','sp_listar_metodos_pago','sp_guardar_metodo_pago','sp_listar_metodos_activos'],true))throw new InvalidArgumentException('Operación inválida.');
 $s=db()->prepare('CALL '.$name.'('.implode(',',array_fill(0,count($params),'?')).')');
 try{$s->execute($params);return $s->columnCount()?$s->fetchAll():[];}finally{$s->closeCursor();}
}
function pagos_validar(array $input):array{
 foreach(['nombre','descripcion','activo'] as $key)if(isset($input[$key])&&!is_string($input[$key]))throw new InvalidArgumentException('Datos inválidos.');
 $name=trim($input['nombre']??'');$description=trim($input['descripcion']??'');$active=$input['activo']??'';
 if($name===''||mb_strlen($name,'UTF-8')>50)throw new InvalidArgumentException('El nombre debe tener entre 1 y 50 caracteres.');
 if(mb_strlen($description,'UTF-8')>150)throw new InvalidArgumentException('La descripción permite hasta 150 caracteres.');
 if(!in_array($active,['0','1'],true))throw new InvalidArgumentException('Selecciona Activo o Inactivo.');
 return [$name,$description,(int)$active];
}
function pagos_error(Throwable $e):string{
 if($e instanceof InvalidArgumentException)return $e->getMessage();
 if($e instanceof PDOException){
  if((int)($e->errorInfo[1]??0)===1062)return 'Ya existe un método de pago con ese nombre.';
  $message=$e->errorInfo[2]??'';
  if((int)($e->errorInfo[1]??0)===1644&&in_array($message,['Esta accion requiere el rol Administrador','Revisa nombre y estado del metodo de pago','El metodo de pago ya no existe'],true))return $message;
 }
 return 'No se pudo completar la operación. Revisa MySQL y vuelve a intentarlo.';
}
