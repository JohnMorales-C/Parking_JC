## Estado actual - unificación del 12 de noviembre de 2026

Java y PHP utilizan parqueadero_jc, con 13 tablas y 47 procedimientos. Se conservaron exclusivamente los datos y las credenciales del escritorio. Los datos de parqueadero_jc_web no se migraron. Las referencias siguientes a bases separadas y pruebas anteriores son históricas. Ver UNIFICACION.md para la configuración vigente.

# CRUD web — avance por módulos

## Estado actual: acceso general terminado
Todos los módulos PHP están protegidos por la sesión general; Usuarios y roles está conectado y el inicio muestra datos reales. Métodos de pago comparte la misma sesión. El responsable de entrada se asigna automáticamente. Ver [ACCESO.md](ACCESO.md) para permisos y pruebas. Las menciones a autenticación pendiente en los registros anteriores describen etapas previas y quedan reemplazadas por este estado.

## Bases independientes — 2 de noviembre de 2026
- Java: parqueadero_jc (conexión Java intacta).
- Web PHP: parqueadero_jc_web. Configuración privada: C:/xampp/parqueadero-private/database.php.
- Se copiaron 13 tablas con sus datos y 45 procedimientos. Se verificaron cantidades, checksums y definiciones de rutinas. No hay sincronización posterior.
- Los datos inicialmente iguales son una copia; los cambios futuros de una aplicación no afectan a la otra. Los procedimientos de la copia no apuntan al esquema original.
- Respaldo SQL, configuración anterior y copia SQL en C:/xampp/parqueadero-private/separacion-20261103-020932 (nombre automático de carpeta según reloj del equipo). No publicar esta carpeta: incluye datos y configuración privada.
- Prueba por Apache: cliente temporal apareció solo en la base web; luego se eliminó. No hubo escrituras en la base original durante la separación.
- Las pruebas de las secciones anteriores se realizaron antes de esta separación; toda prueba futura debe usar únicamente la copia web.


## Clientes: conectado
Abrir http://localhost/Parqueadero_jc_web/php/clientes.php con Apache y MySQL activos.
Permite crear, listar, buscar, ver, editar y eliminar clientes.
Los cambios son reales y exclusivos de la base web parqueadero_jc_web. Java conserva parqueadero_jc.
El documento debe ser único; no se eliminan clientes con vehículos asociados.
Los IDs viajan internamente; el usuario no los introduce.
Valida campos en PHP, escapa HTML, usa consultas preparadas, token CSRF y redirección tras guardar.
Solo se permite acceso desde loopback hasta implementar autenticación y permisos.

## Archivos
- clientes.php: controlador y pantalla.
- php/includes/clientes-service.php: conexión PDO, validación y llamadas a procedimientos.
- assets/clientes.js y clientes.css: búsqueda y ajustes visuales.
- C:/xampp/parqueadero-private/database.php: configuración local privada, fuera de htdocs. No publicar ni subir a Git.
- Las direcciones HTML anteriores se resuelven mediante .htaccess.

Se reutilizan sp_listar_clientes, sp_insertar_cliente, sp_actualizar_cliente y sp_eliminar_cliente.
No se crean ni modifican tablas o procedimientos. Su SQL original permanece en demo/STORED_PROCEDURES.sql del proyecto Java.

## Vehículos: conectado
Abrir http://localhost/Parqueadero_jc_web/php/vehiculos.php.
Crear, listar, buscar, ver, editar y eliminar vehículos con persistencia real.
Seleccionar cliente (nombre y documento), tipo, marca, color y estado; los IDs se usan solo internamente.
La placa se normaliza a mayúsculas y debe ser única, de 1 a 10 caracteres.
Sin clientes, registrar primero uno en Clientes; los otros catálogos se administran mediante SQL.
La eliminación exige confirmación; MySQL impide eliminar vehículos con ingresos registrados.
Se conserva el acceso exclusivamente local y la protección CSRF del primer módulo.

Archivos: vehiculos.php, php/includes/vehiculos-service.php, assets/vehiculos.js.
Reutiliza db() de clientes-service.php y la configuración privada existente.
Procedimientos existentes: sp_listar_vehiculos, sp_insertar_vehiculo, sp_actualizar_vehiculo, sp_eliminar_vehiculo, sp_listar_clientes, sp_listar_tipos_vehiculo, sp_listar_marcas, sp_listar_colores y sp_listar_estados_vehiculo.
No se modificaron tablas ni procedimientos ni código Java.

Pruebas: PHP sin errores de sintaxis; HTTP crear/listar/editar/detalle/confirmar/eliminar; persistencia, normalización, duplicados, validación de catálogos y placa, CSRF, escape HTML, selección al editar y registro inexistente.
La restricción de historial se verificó en el esquema. Se comprobó la protección de Clientes con un vehículo temporal asociado. Se eliminaron exclusivamente los registros temporales propios al finalizar.
Sin nueva revisión visual automatizada.

## Ingresos y Salidas: conectado
Abrir http://localhost/Parqueadero_jc_web/php/ingresos.php.

Flujo:
1. Elegir una placa sin entrada activa; se muestran su tipo y los espacios libres compatibles. Sin JavaScript, pulsar Consultar espacios.
2. Seleccionar espacio y responsable por nombre, y registrar entrada. MySQL asigna la hora utilizada y la rutina ocupa el espacio.
3. En el listado, pulsar Registrar salida. Seleccionar tarifa vigente compatible y método de pago activo.
4. Calcular y revisar; confirmar dentro de 2 minutos. Se conserva la hora del cálculo confirmado. Si vence o cambia la tarifa, recalcular.
5. MySQL guarda tiempo, total, tarifa y método de pago y libera el espacio. Se muestra el total definitivo guardado.

La pantalla permite búsqueda y filtros de movimientos activos/cerrados. El historial no tiene edición ni eliminación.
Los IDs solo viajan como valores internos. Tarifas muestran código comercial y modalidad.
Hasta implementar autenticación real, el responsable se selecciona explícitamente por nombre. El procedimiento comprueba rol habilitado y credencial válida; la selección no equivale a iniciar sesión.
El módulo mantiene acceso exclusivo desde el equipo local.
Si faltan tarifas o métodos activos, se bloquea el cobro y se indica configurarlos desde Java o SQL.

Cobro preservado: minutos completos, horas adicionales proporcionales después de horas_base; DIA y MES cobran valor_base fijo. El cálculo PHP es una estimación y la rutina SQL calcula el importe definitivo. No se acepta un total enviado por el navegador.
Confirmación guardada en sesión, CSRF, fecha de MySQL, comprobación de vigencia y redirección tras guardar.
Se reutilizan sp_registrar_ingreso, sp_registrar_salida, sp_listar_ingresos, sp_obtener_ingreso, sp_listar_espacios_vehiculo, sp_listar_tarifas_salida, sp_listar_metodos_activos y las rutinas de consulta de vehículos, espacios, responsables y tarifas.
No se modificaron tablas, procedimientos ni el proyecto Java.

Archivos: ingresos.php, salida.php, php/includes/ingresos-service.php, php/includes/movimientos-bootstrap.php y assets/ingresos.js.
Las direcciones HTML antiguas de Ingresos y Salidas redirigen al listado real mediante .htaccess; no eligen un movimiento por datos de demostración.

Verificación realizada:
- Sintaxis PHP y páginas servidas por Apache; formulario y selectores inspeccionados en HTML.
- Pruebas HTTP completas con cliente, vehículo, espacios, tarifas y métodos temporales propios.
- Espacios compatibles/incompatibles, entrada persistida, ocupación, entrada duplicada, CSRF de entrada y salida.
- Tarifa incompatible, método inactivo, confirmación, total manipulado ignorado, cobros HORA/DIA/MES, trazabilidad, liberación y rechazo de salida repetida.
- Se eliminaron los registros temporales de esta ejecución. No se editaron registros preexistentes.
- No se realizó nueva revisión visual automatizada.

## Tarifas: CRUD conectado a la base web
Abrir http://localhost/Parqueadero_jc_web/php/tarifas.php.

- Crear, listar, buscar, filtrar por vigencia, consultar detalle, editar y eliminar con confirmación.
- Código automático TAR- seguido de 16 caracteres hexadecimales. UNIQUE en MySQL y hasta cinco reintentos en caso de colisión; no se acepta código manual ni se cambia al editar.
- Tipo de vehículo seleccionado por nombre. Año 2020–2050, horas base 1–24, importes 0–99999999.99 con hasta dos decimales, descripción hasta 150 caracteres.
- Conserva las modalidades HORA, DIA y MES y las reglas actuales. Las nuevas tarifas vigentes se consultan en Salidas desde esta misma base web.
- Tarifas con cobros asociados: únicamente se puede cambiar la vigencia; importes y demás datos quedan protegidos también en el procedimiento. No pueden eliminarse.
- Tarifas sin cobros asociados: se pueden editar o eliminar con confirmación.
- Se mantiene acceso local hasta implementar autenticación real.

Archivos: tarifas.php, php/includes/tarifas-service.php, assets/tarifas.js.
Procedimientos reutilizados: sp_insertar_tarifa, sp_listar_tarifas, sp_existe_codigo_tarifa, sp_listar_tipos_vehiculo y sp_listar_ingresos.
Procedimientos nuevos: sp_actualizar_tarifa y sp_eliminar_tarifa, instalados exclusivamente en parqueadero_jc_web.
Registro SQL: los procedimientos de tarifas están incluidos en parqueadero_jc_web_completo.sql, guardado en la carpeta entrega_sql del proyecto Parqueadero_jc. El archivo parcial tarifas-web.sql fue retirado por ser redundante.
La base web tiene ahora 47 procedimientos; la de Java conserva 45. No se modificaron tablas ni rutinas de Java.

Pruebas por Apache: tres modalidades, persistencia, unicidad y colisión forzada, validaciones, CSRF, escape HTML, formularios de edición, conservación del código, vigencia, eliminación, tarifa inexistente y restricciones por historial. Los registros temporales se eliminaron; se compararon los checksums de las 13 tablas de Java antes/después, sin cambios. Sin nueva revisión visual automatizada.

## Espacios: conectado
Abrir http://localhost/Parqueadero_jc_web/php/espacios.php.
Crear espacios por número único y tipo de vehículo; se crean libres.
Consultar detalle, buscar, filtrar por estado y tipo, y ver contadores de disponibilidad.
Editar únicamente espacios libres. Mantenimiento y habilitación requieren confirmación.
Los espacios ocupados no permiten editar, habilitar, poner en mantenimiento ni eliminar.
La ocupación efectiva considera tanto el estado como la existencia de un ingreso activo.
Los espacios con historial no pueden eliminarse, aunque estén libres; pueden ponerse en mantenimiento.
La disponibilidad se integra directamente con los selectores de Ingresos y Salidas.

Archivos: espacios.php, php/includes/espacios-service.php, assets/espacios.js.
Procedimientos existentes reutilizados: sp_listar_espacios, sp_insertar_espacio, sp_actualizar_espacio, sp_cambiar_estado_espacio, sp_eliminar_espacio, sp_listar_tipos_vehiculo y sp_listar_ingresos.
No se crearon ni modificaron tablas o procedimientos. Las rutinas ya estaban copiadas en parqueadero_jc_web y registradas en su respaldo SQL de separación.

Pruebas HTTP: alta, edición, duplicados, números inválidos, tipo inválido, CSRF, mantenimiento/habilitación y sus confirmaciones, disponibilidad compatible en Ingresos, ocupación efectiva incluso con estado Libre, bloqueo de las cuatro mutaciones cuando hay ingreso activo, historial protegido, eliminación e inexistente. Datos temporales retirados. Checksums de las 13 tablas Java sin cambios. Sintaxis PHP y JavaScript comprobadas. Sin nueva revisión visual automatizada.

## Métodos de pago: conectado con acceso Administrador
Abrir http://localhost/Parqueadero_jc_web/php/pagos.php.
Iniciar acceso con usuario y contraseña de una cuenta Administrador de parqueadero_jc_web. Las cuentas existentes fueron copiadas al separar las bases. No se muestran ni se piden IDs.
Registrar, consultar detalle, buscar, filtrar, editar, activar y desactivar. No se elimina físicamente el método: el historial conserva la referencia.
Nombre único de 1–50 caracteres, descripción opcional hasta 150, estado Activo/Inactivo.
La desactivación lo retira del selector de nuevas salidas; la reactivación lo vuelve a incluir.
Los cambios de estado tienen pantalla de confirmación; el formulario de edición también permite seleccionar estado.

Acceso real limitado a este módulo:
- PBKDF2-HMAC-SHA256 compatible con las cuentas de Java copiadas a la base web (600000 iteraciones).
- Contraseña nunca guardada en texto plano ni enviada al HTML.
- ID de sesión regenerado al entrar y cerrar; CSRF en todos los formularios.
- Expira tras 30 minutos sin actividad en este módulo.
- Revalida cuenta, rol y huella de contraseña en cada petición. La base vuelve a exigir Administrador para las operaciones.
- Después de cinco intentos fallidos, pausa de 30 segundos por sesión.
- No es todavía el inicio de sesión general de la aplicación.

Archivos: pagos.php, php/includes/pagos-service.php, assets/pagos.js.
Se reutilizan sp_listar_metodos_pago, sp_guardar_metodo_pago, sp_listar_metodos_activos, sp_login_usuario y sp_exigir_administrador.
No se modificaron tablas ni procedimientos. El registro SQL de estas rutinas permanece en el respaldo de separación de la base web.

Pruebas por Apache con cuentas temporales propias: rechazo anónimo, contraseña incorrecta, rol Operador, acceso Administrador, revocación de rol, cierre, CSRF, validaciones, creación, nombres duplicados, detalle/edición, desactivación/reactivación, disponibilidad en Salidas e historial intacto.
Se retiraron todas las cuentas y entidades temporales. Checksums de las 13 tablas originales de Java sin cambios. Sintaxis PHP y JavaScript verificadas; sin nueva revisión visual automatizada.

## Incidentes: registro y consulta conectados
Abrir http://localhost/Parqueadero_jc_web/php/incidentes.php.
Registrar incidentes en visitas activas o terminadas, elegidas por placa, fecha de entrada y espacio.
Tipo obligatorio hasta 100 caracteres; descripción obligatoria hasta 5000. Fecha automática de MySQL.
Consultar todos o filtrar por visita; búsqueda por texto y detalle completo con saltos de línea.
Desde Ingresos y Salidas, el enlace Incidentes abre la visita preseleccionada.
Se conserva el comportamiento original de Java: registrar y consultar, sin editar ni borrar el historial.
Los IDs solo se usan internamente; no se solicitan al usuario.

Archivos: incidentes.php, php/includes/incidentes-service.php, assets/incidentes.js, assets/incidentes.css.
Procedimientos reutilizados: sp_listar_movimientos_incidente, sp_listar_incidentes y sp_insertar_incidente.
No se modificaron tablas o procedimientos; definiciones ya presentes en el respaldo SQL de separación de parqueadero_jc_web.
Acceso local; la autenticación general sigue pendiente (el acceso propio de Métodos de pago no se extiende a este módulo).

Pruebas por Apache: visitas activas/cerradas, persistencia, fecha, preselección y filtro por visita, consulta completa, detalle, límites máximos UTF-8, campos inválidos, movimiento inexistente, CSRF, escape HTML, rechazo de acciones de eliminación y movimientos sin alteración.
Datos temporales retirados; checksums de las 13 tablas de Java sin cambios. Sintaxis PHP y JavaScript verificadas. Sin nueva revisión visual automatizada.

## Pendientes
Usuarios y roles ya permite crear, editar, deshabilitar y restablecer cuentas mediante los procedimientos existentes.
Inicio y acceso general ya son funcionales. Todos los módulos PHP comprueban la sesión y los permisos; queda el recorrido visual y manual con las cuentas habituales.
No ejecutar build.ps1 sobre esta carpeta: es el generador de las interfaces iniciales y sobrescribe HTML.

## Verificación realizada
PHP sin errores de sintaxis. Cuatro procedimientos presentes en MySQL. Prueba transaccional revertida y prueba HTTP completa: crear, listar, editar, duplicado, eliminación con confirmación, CSRF, validación y escape HTML. Los registros temporales se eliminaron. No se modificaron registros existentes ni tablas ni procedimientos. No se realizó una nueva revisión visual automatizada del navegador.
