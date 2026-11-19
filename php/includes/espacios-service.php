<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function espacios_call(string $name,array $params=[]): array{
 if(!in_array($name,['sp_listar_espacios','sp_insertar_espacio','sp_actualizar_espacio','sp_cambiar_estado_espacio','sp_eliminar_espacio','sp_listar_tipos_vehiculo','sp_listar_ingresos'],true))throw new InvalidArgumentException('Operación inválida.');
 $s=db()->prepare('CALL '.$name.'('.implode(',',array_fill(0,count($params),'?')).')');
 try{$s->execute($params);return $s->columnCount()?$s->fetchAll():[];}finally{$s->closeCursor();}
}
function espacio_error(Throwable $e):string{
 if($e instanceof InvalidArgumentException)return $e->getMessage();
 if($e instanceof PDOException){
  $n=(int)($e->errorInfo[1]??0);
  if($n===1062)return 'Ya existe un espacio con ese número.';
  if($n===1451)return 'El espacio tiene movimientos asociados y no puede eliminarse.';
  if($n===1644){
   $text=$e->errorInfo[2]??'';
   if(in_array($text,['El espacio ya no existe. Actualiza el listado','El espacio esta ocupado. Registra primero la salida del vehiculo','Estado del espacio no reconocido','Habilita el espacio antes de cambiar su numero o tipo','El numero del espacio debe ser mayor que cero','Selecciona un tipo de vehiculo existente','Solo se permite habilitar o poner en mantenimiento','El espacio tiene movimientos asociados. Puedes ponerlo en mantenimiento, pero no eliminarlo'],true))return $text;
  }
 }
 return 'No se pudo completar la operación. Actualiza el listado y revisa MySQL.';
}
function espacio_estado(array $r):string{
 if(!empty($r['ocupado'])||strtolower(trim($r['estado']??''))==='ocupado')return 'Ocupado';
 return match(strtolower(trim($r['estado']??''))){'libre','disponible'=>'Libre','mantenimiento'=>'Mantenimiento',default=>'No reconocido'};
}
