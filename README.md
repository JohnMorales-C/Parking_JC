# Parking_JC

Sistema web para la gestión de un parqueadero.

**Integrantes:**

- John Steban Morales Ceron
- Carlos Alberto Obando Torrente

**Grupo:** 513

## Descripción

Parking_JC permite administrar clientes, vehículos, espacios, tarifas, métodos de pago y cuentas del personal. Registra las entradas y salidas de vehículos, calcula los cobros, conserva su historial y permite reportar incidentes de cada visita.

La web y la aplicación de escritorio Java utilizan la misma base local MySQL, `parqueadero_jc`. Ambas trabajan sobre los mismos registros, usuarios y reglas de cobro. Este documento describe la versión web.

## Tecnologías

| Tecnología | Uso en el proyecto |
| --- | --- |
| HTML | Estructura de formularios, tablas y demás contenido de las pantallas. |
| CSS | Colores, distribución, tipografía y presentación de los módulos. |
| JavaScript | Filtros, campos dinámicos, consultas de disponibilidad y actualización de datos. |
| PHP | Procesamiento de solicitudes, validaciones, sesiones, permisos y acceso a datos. |
| PDO MySQL | Conexión de PHP con MySQL y ejecución de procedimientos con parámetros. |
| MySQL | Tablas relacionadas, restricciones, procedimientos almacenados, transacciones y triggers de auditoría. |
| Apache / XAMPP | Ejecución local de la web y procesamiento de sus archivos PHP. |

El HTML está escrito dentro de los archivos PHP. No se requieren archivos `.html` separados para cada pantalla. La apariencia se define en `assets/`, mediante CSS compartido y estilos específicos de algunos módulos.

## Estructura del proyecto

```text
Parqueadero_jc_web/
├── README.md
├── .htaccess
├── assets/
│   ├── styles.css
│   ├── app.js
│   ├── actualizacion.js
│   └── estilos y scripts de los módulos
├── php/
│   ├── login.php
│   ├── configuracion.php
│   ├── inicio.php
│   ├── clientes.php
│   ├── vehiculos.php
│   ├── ingresos.php
│   ├── salida.php
│   ├── incidentes.php
│   ├── espacios.php
│   ├── tarifas.php
│   ├── pagos.php
│   ├── usuarios.php
│   ├── auditoria.php
│   ├── clave.php
│   ├── logout.php
│   ├── cambios.php
│   └── includes/
└── documentacion/
```

- `php/`: pantallas y procesamiento de las acciones del usuario.
- `php/includes/`: servicios por módulo, acceso a datos, conexión, sesiones, permisos y funciones compartidas.
- `assets/`: CSS y JavaScript. Por ejemplo, `vehiculos.js` controla interacciones del formulario de vehículos y `tarifas.js` adapta los campos a la modalidad seleccionada.
- `documentacion/`: guías y notas técnicas del desarrollo.
- `.htaccess`: dirige la entrada del sitio al inicio de sesión y mantiene compatibilidad con direcciones anteriores. Otro `.htaccess` restringe el acceso directo a `php/includes/`.

## Instalación local

### Requisitos

- Apache con PHP; el entorno utilizado es XAMPP.
- PHP con PDO MySQL y `mbstring` habilitados.
- Un servidor MySQL con la base `parqueadero_jc`, sus procedimientos y triggers instalados.
- Un navegador con JavaScript habilitado para utilizar filtros y actualización automática.

### Preparar los archivos y la base

1. Copiar la carpeta del proyecto a `C:/xampp/htdocs/Parqueadero_jc_web`.
2. Iniciar Apache desde XAMPP.
3. Iniciar el servidor MySQL utilizado por el proyecto. Puede ser un servicio de Windows independiente de XAMPP; en ese caso, el botón MySQL de XAMPP no controla ese servicio.
4. Si ya existe la base compartida con Java, utilizarla sin volver a importar su estructura.
5. En una instalación nueva, obtener el SQL de estructura del proyecto: `entrega_sql/parqueadero_jc_unificado_estructura.sql`, ubicado en la entrega del escritorio. Incluye tablas, procedimientos y triggers, pero no datos iniciales. El script contiene `DROP`: no debe importarse sobre una base operativa que se quiera conservar.

Las definiciones de los procedimientos también se mantienen en `demo/STORED_PROCEDURES.sql` del proyecto Java. Ese archivo de procedimientos no reemplaza una instalación completa de las tablas.

### Configurar la conexión

Crear `C:/xampp/parqueadero-private/database.php`, fuera de la carpeta pública `htdocs`, con los datos de conexión del equipo:

```php
<?php
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=parqueadero_jc;charset=utf8mb4',
    'user' => 'USUARIO_MYSQL',
    'password' => 'CONTRASENA_MYSQL',
];
```

Los valores en mayúsculas son ejemplos y deben reemplazarse localmente. La cuenta y contraseña de MySQL son distintas de las cuentas con las que el personal inicia sesión en la página. No publicar este archivo ni credenciales reales en el repositorio.

La función `db()` de `php/includes/clientes-service.php` carga este archivo y comparte la conexión PDO con los servicios. Si se cambia la ubicación del archivo privado, también debe ajustarse esa referencia.

### Abrir la aplicación

Acceder a:

```text
http://localhost/Parqueadero_jc_web/
```

O directamente a:

```text
http://localhost/Parqueadero_jc_web/php/login.php
```

Las rutas actuales incluyen `Parqueadero_jc_web`. Renombrar la carpeta requiere revisar las redirecciones y rutas de la aplicación. No se ejecuta abriendo los PHP mediante doble clic.

La versión actual restringe el acceso al equipo local. Una publicación en internet requiere adaptar y revisar la configuración de acceso y despliegue.

## Inicio de sesión y permisos

Las cuentas están guardadas en la tabla `usuario` y su perfil se relaciona con `rol`. Si no hay un administrador habilitado con contraseña configurada, la aplicación permite crear el administrador inicial. Cuando ya existe, esa opción deja de estar disponible.

No hay una contraseña universal para todas las instalaciones. El administrador crea las cuentas del personal desde Usuarios y roles.

| Función | Operador | Administrador |
| --- | --- | --- |
| Consultar Inicio | Sí | Sí |
| Gestionar clientes y vehículos | Sí | Sí |
| Registrar entradas, salidas y consultar movimientos | Sí | Sí |
| Registrar y consultar incidentes | Sí | Sí |
| Gestionar espacios y mantenimiento | No | Sí |
| Gestionar tarifas | No | Sí |
| Gestionar métodos de pago | No | Sí |
| Gestionar usuarios y asignar roles | No | Sí |
| Consultar el módulo de auditoría | No | Sí |
| Cambiar contraseña propia y cerrar sesión | Sí | Sí |

El perfil «Sin acceso» deshabilita el inicio de sesión de una cuenta y conserva sus referencias históricas. No es un tercer perfil operativo. Se protege la existencia de al menos un administrador habilitado.

## Orden para comenzar a utilizar los módulos

Los registros tienen dependencias. Para una instalación nueva se sigue este orden:

1. **Acceso inicial:** configurar el administrador e iniciar sesión.
2. **Catálogos:** preparar mediante SQL los tipos de vehículo, marcas, colores y estados necesarios. No todos los catálogos tienen una pantalla propia de administración.
3. **Configuración operativa:** crear espacios por tipo de vehículo, tarifas vigentes y métodos de pago activos. Crear cuentas de operadores si se necesitan.
4. **Clientes:** registrar al propietario.
5. **Vehículos:** registrar la placa y asociarla al cliente y a sus catálogos.
6. **Ingresos:** elegir el vehículo y un espacio libre compatible.
7. **Incidentes:** si corresponde, registrar un incidente asociado a esa visita. También puede registrarse sobre una visita terminada.
8. **Salidas:** seleccionar el movimiento activo, una tarifa vigente compatible y un método activo; calcular, revisar y confirmar.
9. **Consulta:** revisar movimientos cerrados, condiciones del cobro y responsables. El administrador puede consultar además la auditoría general.

Los incidentes son opcionales. Los espacios, tarifas y métodos de pago pueden prepararse en cualquier orden entre sí, pero deben estar disponibles para completar la operación que los requiere.

## Funciones por módulo

### Inicio

Presenta un resumen de la operación con datos consultados de la base, contadores y movimientos recientes. La navegación muestra las opciones permitidas para el perfil autenticado.

### Clientes

Permite registrar, listar, buscar, consultar detalles, editar y eliminar cuando las relaciones existentes lo permiten. Guarda nombre, documento, teléfono y correo. El documento es único. Un cliente puede tener varios vehículos; no desaparece del selector después de asociarle uno. No se elimina un cliente con vehículos asociados.

### Vehículos

Permite registrar, buscar, consultar, editar y eliminar cuando no existan relaciones que lo impidan. La placa es única y se normaliza a mayúsculas.

El propietario se selecciona por sus datos y puede filtrarse por nombre o documento. El buscador está situado encima del selector de cliente. Se seleccionan tipo, marca y color; las opciones «Otra» u «Otro» permiten indicar el valor correspondiente. El estado se asigna activo al crear y puede cambiarse al editar. Un vehículo con historial de ingresos no se elimina.

### Ingresos y salidas

La entrada se registra seleccionando la placa, sin pedir al usuario el ID del vehículo. Los espacios ofrecidos deben estar libres y ser compatibles con su tipo. El responsable se toma de la cuenta autenticada y se muestra por su nombre.

La operación evita entradas activas duplicadas para un mismo vehículo. Al registrar el ingreso, el espacio queda ocupado. Los movimientos pueden consultarse y filtrarse por su información y estado.

Para una salida se selecciona una tarifa vigente compatible y un método de pago activo. Se calcula el importe y se confirma antes de guardar. El servidor vuelve a validar las condiciones; un total manipulado en el navegador no determina el cobro. Si cambia la tarifa o vence la cotización, se requiere un nuevo cálculo.

El cobro, cierre del ingreso y liberación del espacio se guardan en una transacción. El movimiento cerrado conserva la información histórica y no se ofrece como una entrada activa nueva.

### Incidentes

Permite registrar tipo y descripción de un incidente asociado a una visita. La visita se identifica por placa, entrada y espacio; no se solicita su ID manualmente. La fecha la asigna MySQL.

El filtro por visita limita los incidentes a un movimiento concreto. El buscador de la tabla filtra por texto dentro del listado. Las fechas incluyen el día de la semana para facilitar su lectura y búsqueda. Los incidentes se conservan como historial: no tienen edición ni eliminación desde este módulo.

### Espacios

Permite crear espacios con número único y tipo de vehículo, consultar disponibilidad, buscar, editar y gestionar su estado. Los espacios se crean libres.

Los estados operativos son libre, ocupado y mantenimiento. Un espacio ocupado no puede editarse, ponerse en mantenimiento, habilitarse ni eliminarse mientras exista la ocupación. Un espacio con historial tiene restricciones de eliminación; puede retirarse de la operación mediante mantenimiento cuando esté libre.

Mantenimiento forma parte de la gestión de espacios. No necesita una tabla de espacios diferente para cada tipo de vehículo.

### Tarifas

El código se genera automáticamente y es único. El administrador selecciona el tipo de vehículo, año y modalidad e introduce los valores correspondientes. La descripción es opcional.

| Modalidad | Datos de precio solicitados | Regla |
| --- | --- | --- |
| HORA | Precio por hora | Precio uniforme proporcional a minutos completos. |
| DÍA | Precio del día y precio por hora adicional | Incluye 24 horas desde la entrada; después cobra el excedente. |
| MES | Precio del mes y precio por hora adicional | Incluye 30 días, equivalentes a 720 horas; después cobra el excedente. |

Las pantallas adaptan sus campos al cambiar de modalidad. No solicitan «horas base». La columna SQL correspondiente se conserva por compatibilidad, pero no determina los períodos nuevos de día y mes.

**Vigencia:** toda tarifa nueva se crea vigente y reemplaza a la vigente del mismo tipo de vehículo y modalidad, que pasa a caducada. Una tarifa de otro grupo no se afecta. El cambio de año por sí mismo no caduca una tarifa; la sustitución ocurre al guardar o activar la nueva. También se permite caducar manualmente y reactivar una tarifa, respetando una sola vigente por grupo.

**Edición e historial:** pueden cambiarse los precios sin modificar los cobros ya realizados. Si una tarifa tiene cobros, se protegen su año, tipo y modalidad, y no puede eliminarse. Cada cobro conserva las condiciones que se utilizaron al confirmar la salida.

Ejemplos:

- HORA de 2.000: 30 minutos cuestan 1.000 y 90 minutos cuestan 3.000.
- DÍA de 25.000 con adicional de 2.000/h: 24 horas cuestan 25.000 y 25 horas cuestan 27.000.
- MES de 300.000 con adicional de 2.000/h: 30 días y media hora cuestan 301.000.

Los cálculos utilizan minutos completos y redondeo monetario a dos decimales. No se redondea a una hora iniciada ni se impone una hora mínima. MES representa 30 días y no un mes calendario. En DÍA y MES se suma el excedente al valor del período, sin cobrar automáticamente otro período completo. Si el adicional es cero, no se suma un importe por excedente.

### Métodos de pago

Permite registrar, buscar, consultar, editar, activar y desactivar métodos. El nombre es único y la descripción es opcional. Los métodos inactivos permanecen en el listado administrativo y en el historial, pero no están disponibles para un nuevo cobro. Se desactivan en lugar de eliminar sus referencias históricas.

### Usuarios y roles

El administrador crea cuentas, modifica nombre y usuario, asigna Administrador u Operador, restablece contraseñas o deshabilita el acceso. En una edición, dejar vacíos los campos de contraseña conserva la actual. Los usuarios con referencias históricas no se borran para retirarles el acceso.

Cada usuario puede cambiar su propia contraseña proporcionando la actual. Después del cambio se solicita volver a iniciar sesión. El cierre de sesión termina el acceso a los módulos protegidos.

### Auditoría y responsables

La auditoría guarda operaciones de modificación y permite identificar al usuario, su perfil histórico, la fecha y el origen de la operación: WEB, JAVA o SQL. No equivale a un registro de todas las páginas consultadas.

Las fichas de clientes, vehículos, espacios, tarifas, métodos de pago, usuarios, incidentes y movimientos muestran información del responsable de creación y de la última operación registrada. Así puede consultarse el responsable sin entrar al visor general de auditoría. El Operador accede a esta información en los módulos permitidos para su rol.

Los registros anteriores a la implementación pueden indicar «Sin responsable registrado» o «Sin rol histórico registrado». No se asigna un autor inventado. Las operaciones directas por SQL se identifican como mantenimiento de base de datos según el contexto disponible.

## Base de datos e historial de cobros

La estructura local documentada incluye 15 tablas:

| Grupo | Tablas |
| --- | --- |
| Clientes y vehículos | `cliente`, `vehiculo`, `tipo_vehiculo`, `marca`, `color`, `estado_vehiculo` |
| Acceso | `usuario`, `rol` |
| Operación | `espacio`, `ingreso`, `incidente` |
| Tarifas y pagos | `tarifa`, `metodo_pago`, `cobro` |
| Trazabilidad | `auditoria` |

Las claves foráneas relacionan estas tablas y limitan eliminaciones incompatibles con el historial. Los IDs siguen utilizándose internamente; los selectores presentan nombres, placas y números de espacio.

Las definiciones actuales documentadas incluyen 54 procedimientos y 46 triggers. Los procedimientos realizan consultas y operaciones como registro de clientes, entradas, salidas y gestión de tarifas. Los triggers complementan la auditoría y las restricciones de integridad.

`ingreso` representa la visita y su cierre. `cobro` conserva una instantánea de la tarifa y de los datos utilizados: modalidad, precios, tiempo, total, método y responsable de salida. Cambiar una tarifa no recalcula las ventas anteriores.

El campo `cobro.regla_calculo` distingue reglas históricas y actuales (`ANTERIOR`, `POR_HORA`, `FIJO`, `DIA24_EXTRA` y `MES30_EXTRA`). Esto permite interpretar cobros antiguos sin aplicarles retrospectivamente una fórmula nueva.

## Cómo se procesa una solicitud

Por ejemplo, al guardar un cliente:

1. El navegador envía el formulario HTML de `php/clientes.php`.
2. PHP comprueba sesión, permisos, token del formulario y datos recibidos.
3. El servicio de `php/includes/clientes-service.php` utiliza PDO para llamar al procedimiento correspondiente con parámetros.
4. MySQL comprueba restricciones, guarda la operación y registra la auditoría correspondiente.
5. La aplicación redirige y consulta los datos para mostrar el resultado.

La web separa pantallas, servicios y recursos visuales, aunque una pantalla PHP puede reunir la presentación y el procesamiento de sus acciones. No usa las clases MVC de Java ni necesita ejecutarlas para funcionar. Ambas implementaciones comparten la base y sus reglas.

## Actualización entre Java y web

Las aplicaciones consultan una revisión de datos aproximadamente cada tres segundos mediante `sp_version_datos`. Si se detectan cambios, actualizan los listados y selectores correspondientes.

No es una conexión de notificaciones instantáneas: la demora depende del intervalo, la conexión y la consulta. La web pausa comprobaciones en pestañas ocultas y vuelve a comprobar al regresar. Conserva los borradores y las selecciones que siguen siendo válidas, sin guardar formularios automáticamente. Las operaciones se validan nuevamente en el servidor.

`assets/actualizacion.js` contiene la actualización del navegador y `php/cambios.php` atiende su comprobación. Para demostrar la integración, abrir Clientes en ambas aplicaciones, crear un cliente en una de ellas y comprobar su aparición en la otra tras la siguiente actualización.

## Controles implementados

- Validación de entradas en PHP y comprobaciones adicionales en MySQL.
- Llamadas con parámetros mediante PDO.
- Escape de valores al presentarlos en HTML.
- Tokens CSRF en formularios que modifican información.
- Sesiones y comprobación de permisos del usuario en el servidor.
- Contraseñas almacenadas mediante PBKDF2-HMAC-SHA256, sal aleatoria y 600.000 iteraciones, compatibles con Java.
- Caducidad de sesión tras 30 minutos de inactividad; las consultas automáticas no prolongan por sí solas la sesión.
- Espera de 30 segundos tras cinco intentos fallidos de acceso dentro de la sesión correspondiente.
- Revalidación de cuentas y protección del último administrador.
- Conservación de relaciones e historial, con confirmación de operaciones cuando corresponde.

Estos controles no sustituyen la configuración y los permisos del servidor MySQL. La versión entregada está orientada al uso local.

## Comprobación del flujo completo

1. Entrar como Administrador y preparar espacio, tarifa vigente y método de pago activo.
2. Crear una cuenta Operador y comprobar que no accede a los módulos administrativos.
3. Como Operador, registrar cliente y vehículo.
4. Registrar entrada y comprobar que el espacio deja de ofrecerse como disponible.
5. Asociar un incidente y consultar sus detalles y responsable.
6. Calcular y confirmar la salida; comprobar el total y la liberación del espacio.
7. Como Administrador, revisar el cobro y la auditoría.
8. Editar el precio de la tarifa y comprobar que el cobro anterior conserva el precio y total originales.
9. Comprobar desde Java los registros creados en la web y repetir una consulta en sentido contrario.

La carpeta `documentacion/` conserva notas de verificaciones y etapas anteriores. Algunas incluyen cifras o configuraciones de ese momento; la conexión operativa descrita aquí es la base local compartida `parqueadero_jc`.

## Problemas comunes

| Situación | Qué comprobar |
| --- | --- |
| «Not Found» | Apache activo, carpeta dentro de `htdocs` y ruta exacta utilizada. |
| Falla de conexión a la base | Servicio MySQL, host, puerto, base, credenciales y extensión PDO MySQL. |
| Falta un procedimiento | Que se haya instalado la estructura completa y la conexión apunte a la base correcta. |
| No permite configurar administrador inicial | Ya existe un administrador habilitado; utilizar esa cuenta o solicitar su recuperación autorizada. |
| No hay espacios para la entrada | Tipo compatible, estado libre y ausencia de una ocupación activa. |
| No permite confirmar una salida | Tarifa vigente compatible, método activo y cotización actualizada. |
| No se ven cambios visuales recientes | Recargar con `Ctrl + F5`; si son datos, comprobar también la conexión y sesión. |
| La web no abre desde otro equipo | La implementación actual limita el acceso al equipo local. |

## Documentación complementaria

- [Guía de uso](documentacion/GUIA_USO.md).
- [Acceso y permisos](documentacion/ACCESO.md).
- [Auditoría](documentacion/AUDITORIA.md).
- [Responsables por registro](documentacion/RESPONSABLES_REGISTROS.md).
- [Tarifas y cobros](documentacion/TARIFAS_Y_COBROS.md).
- [Actualización automática](documentacion/ACTUALIZACION_AUTOMATICA.md).
- [Fechas y búsquedas](documentacion/FECHAS_Y_BUSQUEDAS.md).
- [Unificación de la base](documentacion/UNIFICACION.md).
- [Seguimiento de mejoras](documentacion/MEJORAS_PENDIENTES.md).
- [Revisión documentada](documentacion/REVISION_FINAL.md).
