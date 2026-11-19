<?php
declare(strict_types=1);
/** Formato de lectura: no transforma los DATETIME almacenados. */
function fecha_visible(?string $valor):string {
 if(!$valor)return '—';
 try{$fecha=new DateTimeImmutable($valor);}catch(Exception $e){return $valor;}
 $dias=[1=>'lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
 return $dias[(int)$fecha->format('N')].', '.$fecha->format('d/m/Y H:i:s');
}
