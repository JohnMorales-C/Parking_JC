<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function tarifas_call(string $procedure,array $params=[]): array {
 $allowed=['sp_listar_tarifas','sp_insertar_tarifa','sp_existe_codigo_tarifa','sp_actualizar_tarifa','sp_eliminar_tarifa','sp_listar_tipos_vehiculo','sp_listar_ingresos'];
 if(!in_array($procedure,$allowed,true))throw new InvalidArgumentException('Operación inválida.');
 $s=db()->prepare('CALL '.$procedure.'('.implode(',',array_fill(0,count($params),'?')).')');
 try{$s->execute($params);return $s->columnCount()?$s->fetchAll():[];}finally{$s->closeCursor();}
}
function tarifa_validar(array $input,array $types,bool $crear=false): array {
 if($crear)$input['estado']='VIGENTE';
 $input['horas_base']='1';if(($input['tipo_cobro']??null)==='HORA')$input['valor_hora_adicional']='0';
 foreach(['anio','id_tipo','tipo_cobro','horas_base','valor_base','valor_hora_adicional','estado','descripcion'] as $key)
  if(isset($input[$key])&&!is_string($input[$key]))throw new InvalidArgumentException('Datos inválidos.');
 $year=filter_var($input['anio']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>2020,'max_range'=>2050]]);
 $type=filter_var($input['id_tipo']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 $hours=filter_var($input['horas_base']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>24]]);
 if(!$year||!$hours)throw new InvalidArgumentException('Año entre 2020 y 2050; horas base entre 1 y 24.');
 if(!$type||!isset($types[$type]))throw new InvalidArgumentException('Selecciona un tipo de vehículo válido.');
 $mode=$input['tipo_cobro']??'';$state=$input['estado']??'';
 if(!in_array($mode,['HORA','DIA','MES'],true)||!in_array($state,['VIGENTE','CADUCADA'],true))throw new InvalidArgumentException('Selecciona modalidad y vigencia válidas.');
 $amounts=[];
 foreach(['valor_base','valor_hora_adicional'] as $key){
  $value=trim($input[$key]??'');
  if(!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/D',$value))throw new InvalidArgumentException('Los importes deben estar entre 0 y 99999999.99, con máximo dos decimales.');
  $amounts[$key]=$value;
 }
 $description=trim($input['descripcion']??'');
 if(mb_strlen($description,'UTF-8')>150)throw new InvalidArgumentException('La descripción permite hasta 150 caracteres.');
 return ['anio'=>$year,'id_tipo'=>$type,'tipo_cobro'=>$mode,'horas_base'=>$hours,'valor_base'=>$amounts['valor_base'],'valor_hora_adicional'=>$amounts['valor_hora_adicional'],'estado'=>$state,'descripcion'=>$description];
}
function tarifa_crear(array $values,?callable $generator=null): string {
 $values['estado']='VIGENTE';
 for($attempt=0;$attempt<5;$attempt++){
  $code=$generator?$generator():'TAR-'.strtoupper(bin2hex(random_bytes(8)));
  try{tarifas_call('sp_insertar_tarifa',array_merge([$code],array_values($values)));return $code;}
  catch(PDOException $e){
   if((int)($e->errorInfo[1]??0)!==1062 || !tarifas_call('sp_existe_codigo_tarifa',[$code]))throw $e;
  }
 }
 throw new InvalidArgumentException('No se pudo generar un código único. Inténtalo de nuevo.');
}
function tarifa_error(Throwable $error): string {
 if($error instanceof InvalidArgumentException)return $error->getMessage();
 if($error instanceof PDOException){
  $number=(int)($error->errorInfo[1]??0);
  if($number===1451)return 'No se puede eliminar una tarifa con cobros asociados.';
  if($number===1452)return 'El tipo de vehículo ya no existe. Actualiza el formulario.';
  if($number===1644){
   $message=$error->errorInfo[2]??'';
   if(in_array($message,['La tarifa ya no existe','Revisa los valores de la tarifa','La tarifa tiene cobros asociados; no se puede cambiar su ano, tipo o modalidad','No se puede eliminar una tarifa con cobros asociados','No se puede cambiar el precio: hay cobros sin historico'],true))return $message;
  }
 }
 return 'No se pudo guardar la tarifa. Revisa la conexión de MySQL y actualiza el formulario.';
}
