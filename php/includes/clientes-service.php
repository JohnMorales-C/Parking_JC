<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $config = require 'C:/xampp/parqueadero-private/database.php';
        $pdo = new PDO($config['dsn'], $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    // Contexto por conexión: proviene de la sesión, nunca de un campo del formulario.
    $context = $pdo->prepare('CALL sp_contexto_auditoria(?,?,?)');
    $context->execute([$_SESSION['auth']['id']??null,$_SESSION['auth']['fingerprint']??null,'WEB']);
    $context->closeCursor();
    return $pdo;
}
function clientes_call(string $procedure, array $params = []): array {
    $allowed = ['sp_listar_clientes', 'sp_insertar_cliente', 'sp_actualizar_cliente', 'sp_eliminar_cliente'];
    if (!in_array($procedure, $allowed, true)) throw new InvalidArgumentException('Operación inválida');
    $statement = db()->prepare('CALL '.$procedure.'('.implode(',', array_fill(0, count($params), '?')).')');
    try {
        $statement->execute($params);
        return $statement->columnCount() ? $statement->fetchAll() : [];
    } finally { $statement->closeCursor(); }
}
function cliente_validar(array $data): array {
    $values = [];
    foreach (['nombre'=>80, 'documento'=>20, 'telefono'=>20, 'correo'=>80] as $key=>$max) {
        if (isset($data[$key]) && !is_string($data[$key])) throw new InvalidArgumentException('Datos inválidos.');
        $value = trim($data[$key] ?? '');
        if (mb_strlen($value, 'UTF-8') > $max) throw new InvalidArgumentException('El campo '.$key.' supera '.$max.' caracteres.');
        if (in_array($key, ['nombre','documento'], true) && $value === '') throw new InvalidArgumentException('Nombre y documento son obligatorios.');
        $values[$key] = $value;
    }
    if ($values['correo'] !== '' && !filter_var($values['correo'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Introduce un correo válido.');
    return $values;
}
function cliente_error(Throwable $error): string {
    if ($error instanceof InvalidArgumentException) return $error->getMessage();
    if ($error instanceof PDOException) {
        $number = (int)($error->errorInfo[1] ?? 0);
        if ($number === 1062) return 'Ya existe un cliente con ese documento.';
        if ($number === 1451) return 'No se puede eliminar este cliente porque tiene vehículos asociados.';
        if ($number === 1644) return 'El registro ya no existe o sus datos no son válidos. Actualiza el listado.';
    }
    return 'No se pudo completar la operación. Revisa la conexión local de MySQL e inténtalo nuevamente.';
}
