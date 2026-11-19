<?php
declare(strict_types=1);
require_once __DIR__.'/clientes-service.php';
function vehiculos_call(string $procedure, array $params=[]): array {
    $allowed=['sp_guardar_vehiculo_catalogos','sp_listar_vehiculos','sp_insertar_vehiculo','sp_actualizar_vehiculo','sp_eliminar_vehiculo','sp_listar_clientes','sp_listar_tipos_vehiculo','sp_listar_marcas','sp_listar_colores','sp_listar_estados_vehiculo'];
    if(!in_array($procedure,$allowed,true)) throw new InvalidArgumentException('Operación inválida.');
    $statement=db()->prepare('CALL '.$procedure.'('.implode(',',array_fill(0,count($params),'?')).')');
    try {
        $statement->execute($params);
        return $statement->columnCount() ? $statement->fetchAll() : [];
    } finally { $statement->closeCursor(); }
}
function vehiculos_catalogos(): array {
    $config=[
      'id_cliente'=>['Cliente','sp_listar_clientes'],
      'id_tipo'=>['Tipo de vehículo','sp_listar_tipos_vehiculo'],
      'id_marca'=>['Marca','sp_listar_marcas'],
      'id_color'=>['Color','sp_listar_colores'],
      'id_estado'=>['Estado','sp_listar_estados_vehiculo']
    ];
    $catalogs=[];
    foreach($config as $key=>[$label,$procedure]) {
        $catalogs[$key]=['label'=>$label,'options'=>[]];
        foreach(vehiculos_call($procedure) as $row) {
            $name=$row['nombre'];
            if($key==='id_cliente') $name.=' · '.$row['documento'];
            $catalogs[$key]['options'][(int)$row[$key]]=$name;
        }
    }
    return $catalogs;
}
function vehiculo_validar(array $data,array $catalogs,bool $creating=false): array {
    if(!is_string($data['placa']??null)) throw new InvalidArgumentException('La placa es obligatoria.');
    $plate=mb_strtoupper(trim($data['placa']),'UTF-8');
    if($plate==='' || mb_strlen($plate,'UTF-8')>10) throw new InvalidArgumentException('La placa debe tener entre 1 y 10 caracteres.');
    $values=['placa'=>$plate];
    foreach($catalogs as $key=>$catalog) {
        if($creating && $key==='id_estado') {
            $active=null;
            foreach($catalog['options'] as $optionId=>$name)if(mb_strtolower(trim($name),'UTF-8')==='activo'){$active=(int)$optionId;break;}
            if(!$active)throw new InvalidArgumentException('Falta el estado Activo en el catálogo.');
            $values[$key]=$active;
            continue;
        }
        $id=filter_var($data[$key]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$id || !array_key_exists($id,$catalog['options'])) throw new InvalidArgumentException('Selecciona una opción válida para '.$catalog['label'].'.');
        $values[$key]=$id;
    }
    foreach(['id_marca'=>'otra_marca','id_color'=>'otro_color'] as $key=>$field) {
        $other=in_array(mb_strtolower(trim($catalogs[$key]['options'][$values[$key]]),'UTF-8'),['otra','otro'],true);
        $value=$data[$field]??'';
        if(!is_string($value))throw new InvalidArgumentException('Datos inválidos.');
        $value=trim(preg_replace('/\s+/u',' ',$value));
        if($other && ($value===''||mb_strlen($value,'UTF-8')>50||in_array(mb_strtolower($value,'UTF-8'),['otra','otro'],true)))
            throw new InvalidArgumentException($key==='id_marca'?'Especifica una marca de 1 a 50 caracteres.':'Especifica un color de 1 a 50 caracteres.');
        $values[$field]=$other?$value:null;
    }
    return $values;
}
function vehiculo_error(Throwable $error): string {
    if($error instanceof InvalidArgumentException) return $error->getMessage();
    if($error instanceof PDOException) {
        $number=(int)($error->errorInfo[1]??0);
        if($number===1062) return 'Ya existe un vehículo con esa placa.';
        if($number===1451) return 'No se puede eliminar este vehículo porque tiene ingresos registrados.';
        if($number===1452) return 'El cliente o una opción seleccionada ya no existe. Actualiza el formulario.';
        if($number===1644) {
            $message=$error->errorInfo[2]??'';
            if(in_array($message,['Especifica una marca de 1 a 50 caracteres','Especifica un color de 1 a 50 caracteres'],true))return $message;
            return 'El vehículo ya no existe o sus datos no son válidos. Actualiza el listado.';
        }
    }
    return 'No se pudo completar la operación. Revisa la conexión local de MySQL e inténtalo nuevamente.';
}
