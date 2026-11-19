<?php
declare(strict_types=1);
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    http_response_code(403); exit('Este módulo está disponible solamente desde este equipo.');
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
date_default_timezone_set('America/Bogota');
session_name('parqueadero_web');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
require_once __DIR__.'/auth-service.php';
$authUser=auth_require();
require_once __DIR__.'/ingresos-service.php';
function e($value): string {return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function money($value): string {return '$ '.number_format((float)$value,2,',','.');}
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf']; $error=''; $message=$_SESSION['movimiento_flash']??''; unset($_SESSION['movimiento_flash']);
function movimiento_csrf(): void {
 if(!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) {
  http_response_code(403); throw new InvalidArgumentException('El formulario venció. Recarga la página.');
 }
}
