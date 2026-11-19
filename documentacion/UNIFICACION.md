## Ampliación posterior: marcas y colores personalizados
La base compartida tiene ahora 48 procedimientos. Se añadió sp_guardar_vehiculo_catalogos para guardar el vehículo y las opciones nuevas en una sola transacción. Java y PHP lo utilizan al crear o editar vehículos. No se modificaron tablas ni datos preexistentes. Los recuentos de 47 que siguen describen el momento de la unificación.

# Base compartida de Parking JC

Desde el 12 de noviembre de 2026, Java y PHP utilizan `parqueadero_jc` en el mismo servidor MySQL local, puerto 3306.

- Se conservan los datos, IDs y contraseñas del escritorio.
- No se incorporaron los clientes, vehículos, tarifas, movimientos ni incidentes anteriores de la web.
- La base anterior `parqueadero_jc_web` permanece desconectada como copia histórica; no es la base operativa.
- La base compartida tiene 13 tablas y 47 procedimientos. Se añadieron solamente `sp_actualizar_tarifa` y `sp_eliminar_tarifa`; no se alteraron tablas ni registros.
- La configuración PHP está en `C:/xampp/parqueadero-private/database.php`; el DSN debe indicar `dbname=parqueadero_jc`.
- Java mantiene su conexión existente a `parqueadero_jc`.
- Cerrar las sesiones web anteriores y volver a entrar con las cuentas y contraseñas del escritorio. La credencial anterior exclusiva de la web para Operador no se migró.

## Datos iniciales que se conservan

Al unificar, el escritorio tenía catálogos, espacios, métodos de pago y dos cuentas; no tenía clientes, vehículos, tarifas, ingresos ni incidentes. Esos listados empiezan vacíos de forma intencionada. Crear una tarifa vigente desde Administración antes de realizar un cobro.

## Verificación

Se restauró una copia completa del escritorio en una base temporal. En esa copia se probaron entradas y salidas cruzadas mediante el DAO Java y PHP, total de cobro, liberación del espacio, incidente, edición y eliminación protegida de tarifas y compatibilidad del hash PHP con Java. La copia temporal se retiró después. Estas pruebas no equivalen a una revisión visual completa de ambas interfaces.

La migración SQL queda registrada en el repositorio Java: `docs/sql/unificacion-20261112.sql` y `demo/STORED_PROCEDURES.sql`.

Los respaldos privados anteriores a la unificación están en `.backups/` del repositorio Java. No importar un respaldo completo sobre la base compartida sin revisar sus sentencias DROP y USE.