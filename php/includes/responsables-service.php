<?php
/** Solo autoría; el historial completo continúa reservado al administrador. */
function responsable_panel(string $tabla,int $id): void {
 if($id<1)return;
 echo '<section class="card" aria-label="Responsable del registro"><div class="card-head"><h2>Responsable del registro</h2></div><div class="crud-detail">';
 try {
  $actor=auth_require();$s=db()->prepare('CALL sp_responsable_registro(?,?,?)');
  try{$s->execute([$actor['id'],$tabla,$id]);$rows=$s->fetchAll(PDO::FETCH_ASSOC);}finally{$s->closeCursor();}
  if(!array_filter($rows,fn($r)=>$r['momento']==='Creacion'))echo '<p><strong>Creación:</strong> Sin responsable registrado. Este dato es anterior a la auditoría.</p>';
  foreach($rows as $r){
   echo '<p><strong>'.($r['momento']==='Creacion'?'Registrado por':'Última operación por').':</strong> '.h($r['nombre_usuario']).' ('.h($r['usuario']).')</p>';
   echo '<p><strong>Perfil al realizar la operación:</strong> '.h($r['rol_usuario']).'</p><p><strong>Fecha:</strong> '.h($r['fecha']).' · <strong>Origen:</strong> '.h($r['origen']).'</p>';
  }
 }catch(Throwable $e){error_log('Responsable: '.$e->getMessage());echo '<p role="alert">No se pudo consultar el responsable. Actualiza la página o inicia sesión nuevamente.</p>';}
 echo '</div></section>';
}