<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function incidentes_call(string $name,array $params=[]):array{
 if(!in_array($name,['sp_listar_movimientos_incidente','sp_listar_incidentes','sp_insertar_incidente'],true))throw new InvalidArgumentException('Operación inválida.');
 $s=db()->prepare('CALL '.$name.'('.implode(',',array_fill(0,count($params),'?')).')');
 try{$s->execute($params);return $s->columnCount()?$s->fetchAll():[];}finally{$s->closeCursor();}
}
function incidente_error(Throwable $e):string{
 if($e instanceof InvalidArgumentException)return $e->getMessage();
 if($e instanceof PDOException){
  if((int)($e->errorInfo[1]??0)===1452)return 'El movimiento ya no existe. Actualiza las opciones.';
  $message=$e->errorInfo[2]??'';
  if((int)($e->errorInfo[1]??0)===1644&&in_array($message,['Selecciona un movimiento y completa tipo y descripcion (maximo 5000 caracteres)','El movimiento ya no existe. Actualiza las opciones'],true))return $message;
 }
 return 'No se pudo completar la operación. Revisa MySQL y actualiza el listado.';
}
