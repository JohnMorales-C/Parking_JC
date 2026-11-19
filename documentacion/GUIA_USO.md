# Guía de uso y práctica — Parking JC web

Esta guía explica el recorrido que practicamos, las diferencias entre roles y las reglas de los módulos. Las mejoras propuestas al final todavía no están implementadas. Los nombres exactos de algunos botones pueden variar; seguir la acción indicada.

## 1. Abrir el sistema

1. Iniciar Apache desde XAMPP.
2. Comprobar que el servidor MySQL independiente esté funcionando. En esta instalación, no depende del botón MySQL de XAMPP.
3. Abrir http://localhost/Parqueadero_jc_web/.
4. Iniciar sesión con la cuenta correspondiente.

La web y Java utilizan la misma base parqueadero_jc. Se conservaron los datos y las cuentas del escritorio; los datos anteriores de la web no se incorporaron. Los registros creados desde cualquiera de las dos aplicaciones se ven en la otra.

Cuentas consultadas durante la práctica:

| Rol | Usuario | Nombre mostrado |
| --- | --- | --- |
| Administrador | admin | John Morales |
| Operador | operador_demo | Operador de prueba |

Las contraseñas no se incluyen en esta guía. Solicitar acceso al responsable; no confundir el usuario de la aplicación con el usuario root de MySQL.

## 2. Orden correcto: preparación y operación

Antes de recibir vehículos, el Administrador prepara el sistema. Después, el Operador realiza el recorrido diario.

**Preparación recomendada:** acceso de Administrador → Espacios → Tarifas → Métodos de pago → cuentas de Operador si hacen falta.

Espacios, Tarifas y Métodos de pago no dependen entre sí; ese es un orden recomendado. Las dependencias obligatorias son:

- Para registrar un vehículo, primero debe existir su cliente y los catálogos necesarios.
- Para registrar una entrada, deben existir el vehículo y un espacio libre compatible.
- Para registrar un incidente, debe existir una visita o ingreso.
- Para confirmar una salida, debe existir un ingreso activo, una tarifa vigente compatible y un método de pago activo.

**Recorrido diario:** Cliente → Vehículo → Entrada → Incidente, solo si ocurre → Salida y cobro → comprobación del historial y del espacio libre.

Si el cliente y el vehículo ya existen, no se registran otra vez: se comienza por la entrada. Inicio es una pantalla de consulta y puede abrirse en cualquier momento.

## 3. Diferencias entre los roles

| Módulo o función | Operador | Administrador |
| --- | --- | --- |
| Inicio y resumen | Consultar | Consultar |
| Clientes | Gestionar | Gestionar |
| Vehículos | Gestionar | Gestionar |
| Ingresos y Salidas | Registrar y consultar | Registrar y consultar |
| Incidentes | Registrar y consultar | Registrar y consultar |
| Espacios | Seleccionarlos en entradas; sin administrarlos | Crear, editar y gestionar disponibilidad |
| Tarifas | Seleccionarlas al cobrar; sin administrarlas | Crear, editar, cambiar vigencia y eliminar cuando esté permitido |
| Métodos de pago | Seleccionarlos al cobrar; sin administrarlos | Crear, editar, activar y desactivar |
| Usuarios y roles | Sin acceso al módulo | Gestionar cuentas y permisos |
| Auditoría | Sin acceso al visor; sus cambios sí se registran | Consultar historial y valores antes/después |
| Cambiar mi contraseña | Su propia contraseña | Su propia contraseña |

Gestionar no significa que siempre se pueda eliminar: las relaciones y el historial pueden impedirlo. El Administrador también está sujeto a las reglas que protegen los movimientos.

## 4. Preparación del Administrador

### 4.1. Espacios

1. Abrir Espacios.
2. Introducir un número que no exista y seleccionar el tipo de vehículo.
3. Crear y comprobar que aparezca Libre.
4. Para practicar, editar el espacio libre, ponerlo en Mantenimiento y volver a habilitarlo.

El número es único en todo el parqueadero: si existe 99 para Carro, no se puede crear otro 99 para Moto.

- Libre: puede ofrecerse para nuevas entradas compatibles.
- Ocupado: tiene un vehículo dentro; se libera mediante su salida.
- Mantenimiento: no se ofrece para nuevas entradas.

Un espacio ocupado no se edita, elimina ni pasa a mantenimiento. Los espacios con historial no se eliminan; se conserva su trazabilidad.

### 4.2. Tarifas

Crear una tarifa para el mismo tipo de vehículo que se utilizará en la práctica.

Ejemplo:

| Campo | Valor |
| --- | --- |
| Año | Año actual |
| Tipo de vehículo | Carro, si se practicará con un carro |
| Tipo de cobro | HORA |
| Horas base | 2 |
| Valor base | 5000 |
| Valor de hora adicional | 2000 |
| Vigencia | VIGENTE |
| Descripción | Opcional: Tarifa de práctica |

El código se genera automáticamente; no lo escribe el usuario.

- VIGENTE: habilitada para nuevos cobros del tipo de vehículo y modalidad. No caduca automáticamente al cambiar de año; sigue vigente hasta reemplazarla o caducarla.
- CADUCADA: se conserva en administración e historial, pero no se ofrece para nuevos cobros.

Se puede crear como CADUCADA y después editarla para ponerla VIGENTE. La vigencia se refiere a la tarifa, no a un plazo para que salga el vehículo.

En el ejemplo, las primeras dos horas cuestan 5000; después se suma el tiempo adicional proporcionalmente. Las modalidades DIA y MES conservan la regla actual de valor fijo.

Si una tarifa ya se utilizó en cobros, sus condiciones económicas quedan protegidas; se permite cambiar su vigencia. Para un precio nuevo, crear otra tarifa y caducar la anterior. No se elimina una tarifa vinculada a movimientos.

### 4.3. Métodos de pago

1. Revisar si ya hay un método activo, como Efectivo.
2. Para practicar, crear Pago de práctica y añadir una descripción opcional.
3. Editar la descripción, desactivarlo y volver a activarlo.

Un método INACTIVO permanece en administración, pero no aparece ni puede utilizarse para un nuevo cobro. Los movimientos anteriores conservan el método que utilizaron.

Se desactiva en lugar de borrarse. Registrar un método de pago no procesa transferencias ni cobra tarjetas: indica cómo se realizó el pago.

### 4.4. Usuarios y roles

El Administrador puede crear cuentas, editar nombre y usuario, asignar roles, restablecer contraseñas y dejar cuentas Sin acceso.

Para practicar, crear Operador de práctica, usuario operador_practica, rol Operador y contraseña de al menos 12 caracteres. Confirmar la contraseña y guardar. Si el usuario ya existe, utilizar otro.

Sin acceso deshabilita la cuenta y conserva el registro y el historial de su trabajo. Puede volver a habilitarse asignándole Operador o Administrador. Debe permanecer al menos un Administrador habilitado.

## 5. Recorrido diario del Operador

Cerrar la sesión de Administrador y entrar con la cuenta de Operador para comprobar sus permisos.

### 5.1. Clientes

1. Buscar al cliente para evitar duplicarlo.
2. Si no existe, registrar nombre y documento; teléfono y correo son opcionales.
3. Guardar y comprobar que aparezca en el listado.

Ejemplo: Cliente de práctica, documento DEMO-OP-001. Si el documento ya existe, utilizar el registro existente o un documento ficticio diferente.

El documento es único. Un cliente con vehículos asociados no puede eliminarse directamente.

### 5.2. Vehículos

1. Escribir una placa que no exista, por ejemplo PRU123.
2. Buscar al cliente por nombre o documento y seleccionarlo. Las opciones muestran ambos datos. Si no hay coincidencias, cambiar o borrar la búsqueda.
3. Seleccionar tipo, marca y color. El estado Activo se asigna automáticamente al registrar; al editar sí se puede cambiar.
4. Guardar y comprobar que el propietario sea correcto.

Un cliente puede tener varios vehículos: por eso sigue apareciendo en el selector aunque ya sea propietario de otro. La placa del vehículo es única.

Al elegir Otra marca u Otro color aparece un campo obligatorio para escribir el nombre (máximo 50 caracteres). Al guardar, se reutiliza la opción si ya existe o se añade al catálogo para ambos proyectos. Si se vuelve a una opción normal, se descarta el campo adicional.

El estado del vehículo es distinto a su presencia en el parqueadero. Para saber si está dentro, se consulta si tiene un ingreso sin salida. Los registros nuevos se crean como Activo y el estado se puede cambiar al editar. Actualmente Inactivo no impide nuevas entradas.

### 5.3. Entrada

1. Abrir Ingresos y Salidas.
2. Seleccionar la placa del vehículo.
3. Consultar los espacios si la pantalla ofrece esa acción.
4. Elegir un espacio disponible.
5. Registrar la entrada.

Solo se ofrecen vehículos sin entrada activa. El selector de espacios devuelve los del mismo tipo del vehículo, con estado Libre o Disponible y sin ingreso activo asociado. Los ocupados o en mantenimiento quedan excluidos. Al guardar se vuelve a comprobar la selección.

El responsable se toma de la cuenta autenticada: con operador_demo aparece Operador de prueba; con admin aparece John Morales. La hora se registra automáticamente. Los IDs se usan internamente y no se escriben a mano.

Comprobar que el vehículo aparezca en movimientos activos y el espacio quede ocupado.

### 5.4. Incidentes — opcional

1. Abrir Incidentes.
2. Seleccionar la visita por placa, fecha de entrada y espacio.
3. Escribir tipo y descripción de la novedad.
4. Guardar y comprobar el listado.

Ejemplo: tipo Observación de prueba; descripción Registro ficticio para practicar el módulo.

El incidente pertenece a una visita concreta, no solamente a una placa. Se pueden registrar novedades de visitas activas o terminadas. No es obligatorio registrar un incidente para permitir una salida. El módulo conserva el historial y no ofrece edición o eliminación de incidentes.

### 5.5. Salida y cobro

1. Volver a Ingresos y Salidas.
2. Localizar la visita activa y abrir Registrar salida.
3. Seleccionar una tarifa y un método de pago.
4. Calcular o consultar la cotización.
5. Revisar el tiempo y el total.
6. Confirmar la salida. Si la cotización vence, volver a calcular; su validez es de dos minutos.

Si no hay tarifas, el Administrador debe crear o habilitar una para el tipo del vehículo y la modalidad. Solo hay una vigente por grupo; el año no limita su uso. Si no hay métodos de pago disponibles, debe activar uno. El Operador no puede solucionar esas configuraciones desde su cuenta.

Después de confirmar, comprobar:

- Fecha de salida y total guardados en el movimiento.
- Vehículo fuera de la lista de visitas activas.
- Espacio nuevamente libre.
- Tarifa y método de pago conservados en el historial.
- Incidente de prueba todavía asociado a la visita.

## 6. Contraseña y cierre de sesión

Cambiar mi contraseña solicita la contraseña actual y una nueva; después se cierra la sesión. El Administrador puede restablecer la de otros usuarios desde Usuarios y roles. Si no se recuerda la contraseña de Administrador y no se puede entrar, hace falta un procedimiento de recuperación aparte.

Cerrar sesión al terminar, especialmente al alternar entre Administrador y Operador durante la exposición.

## 7. Qué se recorrió y qué quedó pendiente

Durante el recorrido guiado, el usuario confirmó cliente, vehículo, entrada, incidente, espacio, mantenimiento, tarifa y método de pago. Se explicó Usuarios y roles, pero no se confirmó aquí la creación del usuario de práctica.

La salida se dejó pendiente inicialmente por falta de tarifa vigente. Después se creó la tarifa y el método, pero todavía no se confirmó en este recorrido manual que se hubiera cerrado la visita. Completar el apartado 5.5 para terminar la práctica.

Este registro de práctica no sustituye las verificaciones técnicas históricas descritas en REVISION_FINAL.md.

## 8. Mejoras detectadas, todavía pendientes

1. Completada el 12 de noviembre de 2026 en Java y PHP: búsqueda de cliente por nombre o documento, mostrando ambos en las opciones.
2. Completada en Java y PHP: Otra marca permite especificar y conservar el nombre.
3. Completada en Java y PHP: Otro color permite especificar y conservar el nombre.
4. Completada el 15 de noviembre de 2026 en Java y PHP: Activo automático al crear y estado modificable al editar. Inactivo no bloquea actualmente las entradas.

Consultar MEJORAS_PENDIENTES.md para el detalle. Estas propuestas no deben presentarse como funciones ya disponibles.

## Auditoría de operaciones - disponible desde el 15 de noviembre de 2026

El Administrador puede abrir Auditoría en Java o Auditoría de operaciones en el menú web. Se muestran las últimas 300 operaciones, con filtro por tabla, autor, origen y datos antes/después. El Operador genera auditorías al guardar cambios pero no puede consultar el visor. Las operaciones previas a la instalación no tienen auditorías retrospectivas. Consultar AUDITORIA.md para el alcance y el recorrido de demostración.


## Tarifas y cobros históricos - actualizado el 15 de noviembre de 2026

Al activar una tarifa se caduca la anterior del mismo tipo y modalidad, sin importar el año. Las salidas confirmadas conservan los valores aplicados en la tabla cobro. En Java usa Ver cobro histórico; en la web abre la salida registrada. Los cobros anteriores no se recalculan al cambiar precios. Si cambia una tarifa durante la cotización, vuelve a calcular antes de confirmar. Recorrido detallado: TARIFAS_Y_COBROS.md.
