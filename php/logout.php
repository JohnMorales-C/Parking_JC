<?php
require __DIR__.'/includes/web-bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');http_response_code(405);exit('Usa el botón Cerrar sesión.');}
try{auth_csrf();auth_logout();header('Location: login.php',true,303);exit;}catch(Throwable $e){http_response_code(403);echo h(auth_error($e));}
