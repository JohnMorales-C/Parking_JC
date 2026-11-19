<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function movimientos_call(string $procedure,array $params=[]): array {
 $allowed=['sp_registrar_salida_cotizada','sp_listar_ingresos','sp_obtener_ingreso','sp_obtener_ingreso_activo','sp_registrar_ingreso','sp_registrar_salida','sp_listar_vehiculos','sp_listar_espacios','sp_listar_responsables','sp_listar_espacios_vehiculo','sp_listar_tarifas_salida','sp_listar_metodos_activos','sp_listar_tarifas'];
 if(!in_array($procedure,$allowed,true)) throw new InvalidArgumentException('Operación inválida.');
 $statement=db()->prepare('CALL '.$procedure.'('.implode(',',array_fill(0,count($params),'?')).')');
 try { $statement->execute($params); return $statement->columnCount() ? $statement->fetchAll() : []; }
 finally { $statement->closeCursor(); }
}
function movimiento_id($value): int {
 $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 if(!$id) throw new InvalidArgumentException('Selecciona una opción válida.');
 return $id;
}
function movimiento_find(array $rows,string $key,int $id): array {
 foreach($rows as $row) if((int)$row[$key]===$id) return $row;
 throw new InvalidArgumentException('La opción seleccionada ya no está disponible. Actualiza el formulario.');
}
function movimiento_now(): string { return (string)db()->query('SELECT NOW()')->fetchColumn(); }
function movimiento_total(array $entry,array $rate,string $exit): array {
 $timezone=new DateTimeZone('UTC'); // Fechas MySQL sin zona: diferencia de sus valores de calendario.
 $seconds=(new DateTimeImmutable($exit,$timezone))->getTimestamp()-(new DateTimeImmutable($entry['fecha_entrada'],$timezone))->getTimestamp();
 if($seconds<0) throw new InvalidArgumentException('La salida no puede ser anterior a la entrada.');
 $minutes=intdiv($seconds,60);
 // Enteros en centavos: misma regla proporcional por minutos y redondeo a 2 decimales que MySQL.
 $base=(int)round((float)$rate['valor_base']*100);
 $hora=$rate['tipo_cobro']==='HORA';
 $incluidos=$rate['tipo_cobro']==='DIA'?1440:43200;
 $minutosCobro=$hora?$minutes:max(0,$minutes-$incluidos);
 $precio=$hora?$base:(int)round((float)$rate['valor_hora_adicional']*100);
 $fijo=$hora?0:$base;
 if($precio>0 && $minutosCobro>intdiv((9999999999-$fijo)*60+29,$precio))throw new InvalidArgumentException('El cobro supera el importe permitido');
 $cents=$fijo+intdiv($precio*$minutosCobro+30,60);
 return ['horas'=>round($minutes/60,2),'total'=>$cents/100];
}
function movimiento_error(Throwable $error): string {
 if($error instanceof InvalidArgumentException) return $error->getMessage();
 if($error instanceof PDOException && (int)($error->errorInfo[1]??0)===1644) {
  $text=(string)($error->errorInfo[2]??'');
  $allowed=['El vehiculo ya no existe','El espacio ya no esta disponible','El espacio no corresponde al tipo de vehiculo. Actualiza las opciones','El vehiculo o el espacio ya tiene un ingreso activo','Fecha de entrada invalida','El responsable no tiene acceso habilitado','El movimiento ya no existe','Este movimiento ya tiene salida','La salida no puede ser anterior a la entrada','Selecciona una tarifa vigente del tipo de vehiculo','La tarifa contiene valores invalidos','El metodo de pago no esta activo. Actualiza las opciones y selecciona otro'];
  if(in_array($text,$allowed,true)) return $text;
  if(in_array($text,['La tarifa cambio. Calcula nuevamente el cobro','El cobro supera el importe permitido'],true))return $text;
 }
 return 'No se pudo completar la operación. Actualiza el listado y revisa la conexión de MySQL.';
}
