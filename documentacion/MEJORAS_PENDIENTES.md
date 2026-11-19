# Mejoras pendientes

Hallazgos del recorrido de uso. Registrar aquí las mejoras para implementarlas posteriormente; esta lista no implica que ya estén aplicadas.

## 1. Vehículos: buscar al cliente en el selector

Estado: implementada en Java y PHP el 12 de noviembre de 2026.

Problema: al existir muchos clientes, recorrer un selector convencional para encontrar al propietario resulta difícil.

Mejora acordada para evaluar e implementar después:
- Incorporar búsqueda por nombre o documento en el selector de cliente.
- Mostrar nombre y documento en las opciones para distinguir homónimos.
- Conservar la selección del cliente y el almacenamiento de su ID interno.
- Permitir que un mismo cliente siga asociado a varios vehículos.

Comprobación esperada: encontrar y seleccionar al propietario entre 100 o más clientes escribiendo parte del nombre o documento, y guardar el vehículo con el cliente correcto.

Resultado: búsqueda por nombre o documento, ignorando mayúsculas y tildes. Las opciones muestran nombre y documento. El filtro conserva el propietario si sigue visible; si deja de coincidir, exige seleccionarlo de nuevo y no asigna otro automáticamente. Editar recupera el cliente por su ID y Limpiar reinicia la búsqueda. La relación de varios vehículos por cliente se conserva.

Verificación: compilación principal y de pruebas Java; prueba Swing sin MySQL con 120 clientes; lógica JavaScript en DOM simulado con 120 opciones; sintaxis PHP; respuesta HTTP del formulario para Administrador y Operador. Pendiente revisión visual final en navegador. Sin modificaciones de tablas, procedimientos ni registros de negocio.

## 2. Vehículos: especificar otra marca

Estado: implementada en Java y PHP el 12 de noviembre de 2026.

Hallazgo: el selector contiene la opción «Otra», pero no muestra un campo para escribir la marca que falta.

Comportamiento esperado:
- Mostrar «Especifica la marca» al elegir «Otra» y exigir un valor no vacío.
- Conservar el nombre indicado para consultarlo después.
- Definir cómo se integra con el catálogo de marcas y evitar duplicados, respetando las relaciones actuales y los permisos de Operador.
- Ocultar el campo y dejar de exigirlo cuando se seleccione una marca existente.


Resultado: campo condicional obligatorio de máximo 50 caracteres. Se reutiliza el registro existente o se crea la opción al guardar el vehículo, en una misma transacción. Un error revierte también las opciones nuevas. Los campos ocultos se descartan. La base compartida conserva los IDs y las relaciones actuales.

Verificado: crear desde DAO Java, leer y editar desde PHP/SQL, evitar duplicados por mayúsculas o espacios, rechazar vacíos y nombres largos, revertir por placa duplicada y validar campos Swing/JavaScript/PHP. Nuevo procedimiento compartido sp_guardar_vehiculo_catalogos, registrado en docs/sql/vehiculos-otros-catalogos-20261112.sql y demo/STORED_PROCEDURES.sql. No se modificaron tablas ni registros previos.

## 3. Vehículos: especificar otro color

Estado: implementada en Java y PHP el 12 de noviembre de 2026.

Hallazgo: el selector contiene la opción «Otro», pero no muestra un campo para escribir el color que falta.

Comportamiento esperado:
- Mostrar «Especifica el color» al elegir «Otro» y exigir un valor no vacío.
- Conservar el nombre indicado para consultarlo después.
- Definir cómo se integra con el catálogo de colores y evitar duplicados, respetando las relaciones actuales y los permisos de Operador.
- Ocultar el campo y dejar de exigirlo cuando se seleccione un color existente.


Resultado: campo condicional obligatorio de máximo 50 caracteres. Se reutiliza el registro existente o se crea la opción al guardar el vehículo, en una misma transacción. Un error revierte también las opciones nuevas. Los campos ocultos se descartan. La base compartida conserva los IDs y las relaciones actuales.

Verificado: crear desde DAO Java, leer y editar desde PHP/SQL, evitar duplicados por mayúsculas o espacios, rechazar vacíos y nombres largos, revertir por placa duplicada y validar campos Swing/JavaScript/PHP. Nuevo procedimiento compartido sp_guardar_vehiculo_catalogos, registrado en docs/sql/vehiculos-otros-catalogos-20261112.sql y demo/STORED_PROCEDURES.sql. No se modificaron tablas ni registros previos.

## 4. Vehículos: simplificar el estado al registrar

Estado: implementada en Java y PHP el 15 de noviembre de 2026.

- Los vehículos nuevos se guardan como Activo automáticamente. En Java el selector queda deshabilitado al crear; en PHP se muestra un campo de solo lectura.
- El estado se habilita para selección al editar y se recupera el estado real del vehículo. Limpiar vuelve al modo de creación con Activo.
- PHP y las rutinas MySQL ignoran el estado enviado al crear. Se busca Activo por nombre, sin depender de un ID fijo. Si falta en el catálogo, se informa el error.
- Se actualizaron sp_insertar_vehiculo y sp_guardar_vehiculo_catalogos. No se modificaron tablas, estados ni registros existentes. El total sigue siendo 48 procedimientos.
- La revisión confirmó que Inactivo no bloquea actualmente nuevas entradas. Esta mejora no añadió esa restricción: el estado del registro no indica si el vehículo está estacionado.
- SQL registrado en docs/sql/vehiculos-estado-activo-20261115.sql y demo/STORED_PROCEDURES.sql. Exportación de estructura compartida actualizada.

Verificado: creación desde DAO Java y PHP/SQL en una copia temporal, estado manipulado ignorado al crear, edición a Inactivo y Activo, rechazo de estado inválido al editar, detección de catálogo Activo ausente, pruebas Swing de estado inicial/edición/limpieza, validación PHP y regresiones del buscador y de otros catálogos. Sintaxis, HTTP por rol e importación del SQL comprobados. La revisión visual completa sigue pendiente.

## Correcciones solicitadas por el profesor

Solicitud original: «Guardar auditorías con el usuario, verificar las funcionalidades de tarifas de guardar en las ventas los hechos históricos, pero en los cálculos sólo la vigente».

Alcance: contemplar Java y PHP, que ahora comparten la base parqueadero_jc. Los puntos siguientes registran la interpretación para diseñar e implementar; no afirman que ya estén completos.

## 5. Auditoría de operaciones con el usuario responsable

Estado: implementada en Java y PHP el 15 de noviembre de 2026.

Se auditan altas, cambios reales y bajas en las 13 tablas de negocio y catálogos. Se conserva usuario, nombre histórico, fecha y hora, aplicación de origen, acción, tabla, ID y valores antes/después. El responsable de entrada y el usuario que confirma una salida quedan diferenciados. No se guardan contraseñas, hashes ni firmas de sesión.

La consulta de las últimas 300 operaciones por tabla es exclusiva del Administrador en ambas aplicaciones. La auditoría no se edita ni elimina desde las aplicaciones. Los cambios directos de SQL se identifican como mantenimiento; no se atribuyen a una cuenta de aplicación sin conocerla. Las lecturas e inicios/cierres de sesión quedan fuera de esta primera auditoría de modificaciones.

Implementación compartida: tabla auditoria, tres procedimientos de contexto/registro/consulta y 41 triggers. SQL aplicado en docs/sql/auditoria-20261115.sql; procedimientos registrados en demo/STORED_PROCEDURES.sql y esquema exportado actualizado. La base ahora tiene 14 tablas y 51 procedimientos.

Pruebas: CRUD Java con sesión real, operaciones PHP/SQL con autores diferentes, historial antes/después, reversión, contraseña sin secretos, permisos e inmutabilidad. Formularios PHP reales en servidor aislado: primer administrador, login, alta de Operador y cliente auditado desde la sesión. Datos previos intactos. Guía: AUDITORIA.md.

## 6. Tarifas: conservar los hechos históricos del cobro y calcular con la vigente

Estado: implementada en Java y PHP el 15 de noviembre de 2026.

Regla elegida por el usuario: una tarifa vigente por tipo de vehículo y modalidad, hasta que se reemplace, aunque cambie el año. Activar una nueva caduca la anterior de ese grupo. Un índice único en MySQL impide duplicidades; HORA, DIA y MES siguen siendo modalidades diferentes.

Cada salida confirmada guarda una instantánea inmutable en cobro con tarifa, condiciones económicas, minutos, total, placa/tipo, método y usuario de salida. Java y PHP muestran el histórico guardado sin recalcularlo con las tarifas actuales. Se rechazan tarifas caducadas e importes distintos a los cotizados. Se conserva HORA proporcional por minutos y DIA/MES a valor fijo.

Java incorpora edición/reactivación y eliminación protegida de tarifas, más Ver cobro histórico. Las tarifas usadas solo permiten cambiar vigencia. El visor de auditoría de ambos proyectos incluye cobro.

No había cobros anteriores en la base operativa; no se inventó histórico retrospectivo. Los datos previos quedaron intactos. SQL aplicado: docs/sql/tarifas-cobros-historicos-20261115.sql; procedimientos registrados en demo/STORED_PROCEDURES.sql y exportación actualizada. Base actual: 15 tablas, 52 procedimientos y 46 triggers.

Verificado: operaciones cruzadas Java/PHP, formularios web reales en servidor aislado, reemplazo entre años, reactivación, unicidad, reversión, cotización obsoleta, inmutabilidad y consulta histórica tras cambiar precios o nombre de pago. Guía y demostración: TARIFAS_Y_COBROS.md.

## Cierre pendiente

Las seis mejoras funcionales están implementadas en ambos proyectos. Falta el recorrido visual y manual final como Administrador y Operador, especialmente registrar una entrada en una aplicación y cerrar la salida en la otra, y actualizar el PDF de la guía para la entrega final.
## 7. Responsable visible en cada registro

Implementado en Java y PHP el 15 de noviembre de 2026. Las fichas de clientes, vehículos, espacios, tarifas, métodos de pago, usuarios, incidentes e ingresos/salidas muestran quién creó el registro y quién realizó la última operación: nombre y usuario históricos, perfil al operar, fecha y origen (JAVA, WEB o SQL). En Java se consulta al seleccionar una fila; en la web al abrir Ver o editar una ficha. El cobro histórico de Java también incluye responsables. Usuarios incorpora una ficha Ver de solo lectura en la web.

La consulta se actualiza al abrir o volver a seleccionar el registro; no requiere entrar al módulo Auditoría. No hay actualización automática entre equipos si una ficha permanece abierta: vuelve a abrirla/seleccionarla para consultar cambios nuevos.

El perfil se guarda desde esta mejora y no cambia al renombrar la cuenta o cambiarle el rol. Los eventos anteriores conservan su autor conocido, pero muestran “Sin rol histórico registrado”. Si el registro precede a la auditoría, se indica “Sin responsable registrado”: no se inventan creadores. Las operaciones SQL se identifican como mantenimiento de base de datos.

El Operador puede consultar responsables en sus propios módulos, sin obtener acceso al visor completo ni a los detalles de usuarios, tarifas, espacios o métodos de pago. La consulta no expone contraseñas ni hashes.

SQL: docs/sql/responsables-registros-20261115.sql; actualización registrada en demo/STORED_PROCEDURES.sql y entrega_sql/parqueadero_jc_unificado_estructura.sql. Base actual: 15 tablas, 53 procedimientos, 46 triggers. Respaldo anterior: .backups/unificacion_20261115_122506/.

Verificación: formularios HTTP y Java reales en una copia temporal; creación de Operador y edición de Administrador, conservación del perfil tras cambiar el rol, las ocho fichas, registros antiguos, escape HTML y restricciones. Java compila; ambos archivos SQL completos importan con 53 procedimientos. Pruebas: tmp/probar_responsables.php y ResponsableRegistroIntegracionTest (solo QA). Queda el recorrido visual manual y actualizar el PDF de entrega.

## 8. Actualización automática entre Java y la web

Implementada el 15 de noviembre de 2026 para la base local compartida parqueadero_jc. Se consulta una revisión de auditoría cada 3 segundos y se actualizan los datos al detectar cambios. No es push: el tiempo visible depende del intervalo y de la consulta. Java actualiza la vista abierta y vuelve a comprobar al navegar o recuperar el foco; la web pausa las consultas en pestañas ocultas y vuelve a comprobar al regresar.

Los listados y selectores incorporan nuevos datos; se conservan búsquedas, borradores, contraseñas en curso, valores numéricos y selecciones por ID que sigan disponibles. La web actualiza también el resumen del inicio y los responsables. Las opciones eliminadas o no disponibles no se sustituyen automáticamente por otra opción. Java bloquea Guardar si desaparece la fila que se editaba, hasta limpiar o seleccionar otra. La web conserva los formularios y vuelve a validar disponibilidad en el servidor al guardar.

La comprobación de revisión de Java se realiza en segundo plano; la aplicación de datos utiliza las recargas actuales de cada vista. La web utiliza fetch y actualiza regiones del DOM, sin recargar toda la página ni enviar formularios automáticamente. Los fallos de conexión se indican sin borrar borradores. Las consultas web automáticas no prolongan por sí solas el plazo de inactividad de la sesión.

SQL nuevo: docs/sql/actualizacion-automatica-20261115.sql, procedimiento sp_version_datos, con contexto autenticado. La revisión combina cantidad, máximo y suma de IDs de auditoría para detectar también confirmaciones de transacciones fuera de orden. No registra lecturas en auditoría ni expone sus detalles. No se modificaron tablas ni datos de negocio. Estado: 15 tablas, 54 procedimientos y 46 triggers. Procedimientos maestros y exportación de estructura actualizados. Respaldo previo: .backups/unificacion_20261115_153255/.

Verificado: alta real HTTP y DAO Java en una copia temporal, cambios de revisión sin escrituras por leer, permisos y sesiones, conservación de borradores/selección y bajas en Swing; script web en DOM simulado con nuevos registros, cliente nuevo en buscador, filtros, opciones eliminadas, selector abierto, reconexión, pestaña oculta y sesión expirada. HTTP operativo de 12 pantallas por rol, compilación Java, sintaxis PHP e importación de los SQL. Queda la observación visual final con ambas aplicaciones abiertas.

Para probar: reiniciar Java; recargar la web con Ctrl+F5; dejar Clientes abierto en Java y crear un cliente desde la web. El registro debe aparecer tras la siguiente comprobación. Repetir al revés y dejar un borrador sin guardar para comprobar que se conserva. Probar además una entrada/salida para comprobar la disponibilidad de espacios.

## 9. Vigencia automática al crear y edición de precios

Implementada el 15 de noviembre de 2026 en Java y PHP. Toda tarifa nueva queda VIGENTE por decisión del sistema y caduca la vigente anterior del mismo tipo de vehículo y modalidad. Crear la de 2027 reemplaza la de 2026 al guardar; no se programa una activación para enero ni se caducan otros grupos. En creación, la vigencia se muestra de solo lectura.

Al editar se pueden cambiar precios, horas base, descripción y vigencia; se permite caducar manualmente y se conserva la reactivación existente. Las tarifas con cobros conservan protegidos su año, tipo de vehículo y modalidad. Los cobros previos mantienen sus instantáneas inmutables. Si faltase una instantánea de un cobro cerrado, el servidor bloquea el cambio económico. Una cotización anterior a cambiar precios exige recalcular.

Regla compartida en MySQL: docs/sql/tarifas-vigencia-automatica-20261115.sql, actualiza sp_insertar_tarifa y sp_actualizar_tarifa; SQL maestro y exportación de estructura actualizados. Sin cambios de tablas ni registros existentes. Estado: 15 tablas, 54 procedimientos y 46 triggers. Respaldo: .backups/unificacion_20261115_155901/.

Pruebas aisladas HTTP y Java: estado de alta manipulado ignorado, reemplazo 2026/2027, independencia de otros tipos/modalidades, edición antes y después de cobrar, rechazo de cotización obsoleta, caducidad manual, auditoría, identidad protegida, reversión por código duplicado y conservación exacta de cobros históricos. Guía actualizada: TARIFAS_Y_COBROS.md. Esta mejora sustituye la anterior restricción de editar únicamente la vigencia en tarifas usadas.

## 10. Rueda del mouse en Java

Corregido el 15 de noviembre de 2026. Las listas y áreas de texto anidadas conservan el desplazamiento del tema FlatLaf y ceden la rueda a la página exterior al alcanzar el borde superior/inferior o cuando no tienen contenido desplazable. Shift conserva el eje horizontal.

Las recargas automáticas conservan la posición de los viewports y evitan restauraciones innecesarias del cursor. Las consultas asíncronas del responsable conservan también la posición de lectura. Si el usuario mueve la rueda o arrastra una barra entre una actualización y su restauración diferida, se respeta el movimiento nuevo. La aplicación de actualizaciones se pospone mientras se detecta desplazamiento reciente.

Solo cambia presentación de Java; no hay cambios en PHP, tablas, procedimientos ni datos. Prueba: DesplazamientoTest, eventos Swing reales en modo sin ventanas con tema estándar y FlatLaf: rueda arriba/abajo, bordes, lista corta, conservación de posiciones y movimiento nuevo del usuario. Compilación principal y de pruebas verificada. Pendiente confirmación visual con el mouse del usuario tras reiniciar Java.

## 11. Campos de tarifas según modalidad

Implementado en Java y PHP el 15 de noviembre de 2026. HORA muestra Horas base, Valor base y Hora adicional. DÍA y MES ocultan Horas base y Hora adicional, y el precio se identifica como Valor por día o Valor por mes. Cambiar de modalidad no borra los valores que se estaban escribiendo para HORA. Los campos ocultos no bloquean el guardado de tarifas fijas.

Los detalles y listados muestran únicamente valores horarios aplicables: en columnas compartidas se usa un guion para las modalidades fijas. Se conservan el esquema, los procedimientos y los cálculos actuales: DÍA y MES siguen cobrando un importe fijo. No hay caducidad por calendario ni cobros extra nuevos. En las altas/ediciones de tarifas fijas, los parámetros internos no aplicables utilizan 2 horas y adicional 0 para mantener la firma existente. No se modificaron datos operativos ni se ejecutaron migraciones.

Verificado: formulario Swing al alternar HORA/DÍA/MES, script web en DOM simulado, sintaxis PHP y guardado real por HTTP de DÍA/MES sin enviar campos horarios en una base temporal. Se repitieron las pruebas de vigencia, edición de precios y conservación de cobros históricos. Reiniciar Java y recargar la web con Ctrl+F5.

## 12. HORA con precio uniforme

Implementada en Java, PHP y MySQL el 15 de noviembre de 2026. Sustituye el bloque inicial y adicional de las versiones anteriores: un solo Precio por hora, proporcional a minutos completos. DÍA y MES siguen fijos. Todas las pantallas de tarifa muestran un precio por modalidad; se retiraron los campos y columnas de adicionales.

Los cobros previos conservan sus importes y condiciones. La nueva columna cobro.regla_calculo identifica ANTERIOR, POR_HORA o FIJO para explicar el histórico correctamente. No había tarifas HORA creadas al aplicar: no se convirtieron precios existentes. Contenido anterior de las 15 tablas verificado sin cambios; respaldo .backups/unificacion_20261115_165552/.

SQL: docs/sql/hora-uniforme-20261115.sql; seis procedimientos y trigger de auditoría actualizados, con registro en maestro y exportación. Estado: 15 tablas, 54 procedimientos, 46 triggers. Pruebas Java/PHP/MySQL sobre una copia aislada, importación SQL y HTTP operativo por rol: OK. Guía vigente: TARIFAS_Y_COBROS.md. Reiniciar Java y recargar web con Ctrl+F5.

## 13. Adicional después de DÍA o MES

Reglas elegidas por el usuario e implementadas en Java, PHP y MySQL: DÍA cubre 24 horas y MES 30 días desde el ingreso; después se suma el precio por hora adicional proporcional a minutos completos. HORA conserva su precio uniforme. El campo adicional solo aparece en DÍA/MES; no se reintroducen Horas base. Con adicional 0 no se suman cargos; no se inventaron precios para tarifas existentes.

Los nuevos cobros guardan reglas DIA24_EXTRA o MES30_EXTRA, además de precio base, adicional y tiempo; su detalle muestra período incluido y minutos adicionales. Los anteriores ANTERIOR, FIJO y POR_HORA conservan su significado y datos. Cambiar la tarifa no reescribe cobros.

SQL: docs/sql/dia-mes-adicional-20261115.sql actualiza sp_insertar_tarifa, sp_actualizar_tarifa y sp_registrar_salida_cotizada. Maestro y exportación actualizados. Estado: 15 tablas, 54 procedimientos, 46 triggers. Sin cambios de tablas ni datos existentes. Respaldo: .backups/unificacion_20261115_171333/.

Pruebas en copia aislada: Java/PHP/MySQL en límites y excedentes de 24h/30d, fracciones y redondeo, formularios de alta/edición, HORA sin adicional, rechazo de cotización obsoleta y valores negativos, autoría y conservación histórica tras editar. HTTP operativo por rol e importación SQL verificados. Guía: TARIFAS_Y_COBROS.md.

## 14. Selector de modalidad de Tarifas en la web

Corregida la actualización de la etiqueta del precio mediante un span identificado, sin depender del primer nodo de texto del label. HORA muestra Precio por hora; DÍA, Precio por día con 24 horas; MES, Precio por mes con 30 días. El campo Hora adicional aparece únicamente en DÍA/MES. Se versionan JavaScript y CSS del módulo para evitar copias anteriores al recargar.

Verificado: script real en DOM simulado al alternar las tres modalidades y tras el evento de actualización automática; respuesta HTTP autenticada con etiqueta estable y archivo versionado idéntico al instalado. Sin cambios de cálculos ni base de datos.

## 15. Organización del filtro de cliente en Vehículos

En la web, Color ocupa la posición superior junto a Placa; búsqueda y selector de Cliente quedan agrupados en una sola columna, con el filtro encima. Marca/Color conservan sus campos condicionales Otra/Otro dentro del mismo grupo. La distribución se adapta a tres, dos o una columna según el ancho.

Java mantiene el mismo agrupamiento con etiquetas separadas para búsqueda y Cliente y reubica Color arriba. No se modificaron IDs, búsquedas, validaciones, operaciones ni base de datos. Verificados sintaxis PHP, compilación Java y búsqueda existente de clientes. Recargar web y reiniciar Java para ver la nueva distribución.

## 16. Buscador de texto de incidentes en Java

Añadido Buscar en los incidentes mostrados, equivalente al buscador de la tabla web: filtra al escribir por placa, tipo o descripción y otros datos visibles, sin distinguir mayúsculas. Usa coincidencias literales, sin interpretar expresiones regulares. Se combina con la visita seleccionada y se conserva al actualizar el listado. Borrar el texto quita la búsqueda; Ver todos quita el filtro de visita. El contador distingue resultados filtrados y ausencia de coincidencias.

La web ya disponía de ese buscador y conserva su funcionamiento. No se modificaron base de datos, permisos ni operaciones. Verificado sin MySQL: búsqueda por placa/tipo/descripción, caracteres especiales, combinación con visita, detalle correcto después de filtrar/ordenar y conservación al recargar; compilación Java correcta. Prueba: BusquedaIncidentesTest. Reiniciar Java para ver el campo nuevo.

## 17. Día de semana y búsqueda por fecha

Java y la web muestran día de semana en español junto a fecha y hora de entrada, por ejemplo lunes, 02/11/2026 08:30:00. Se aplica a listados de movimientos, incidentes, selección de visitas y detalles de ingreso/salida; la web incluye movimientos recientes del inicio. Las fechas se conservan completas para distinguir semanas diferentes.

Las búsquedas de ingresos e incidentes reconocen días sin distinguir mayúsculas ni tildes (miercoles encuentra miércoles, sabado encuentra sábado), además de fecha visible y formato ISO anterior. Java incorpora buscador en Ingresos y Salidas, preservando la conversión de filas visibles a IDs internos. El filtro de incidentes sigue combinándose con la visita seleccionada y se conserva al actualizar.

No se almacenan columnas redundantes de día ni se cambian los DATETIME o cálculos. La etiqueta compartida de visita se actualiza mediante sp_listar_movimientos_incidente: docs/sql/dias-entrada-20261115.sql, SQL maestro y exportación actualizados. Estado: 15 tablas, 54 procedimientos, 46 triggers. Datos originales verificados sin cambios. Respaldo: .backups/unificacion_20261115_190522/.

Verificado: FechasBusquedaTest y BusquedaIncidentesTest sin MySQL; siete días y búsqueda por fecha visible/ISO; scripts web reales en DOM simulado; etiquetas SQL y pantallas HTTP con siete visitas en copia temporal; sintaxis PHP, compilación Java, importación SQL y HTTP operativo por rol. Reiniciar Java y recargar web para visualizar.

## 18. Nombre visible Parking JC

Desde el 16 de noviembre de 2026, el nombre visible del producto es Parking JC. Se actualizaron títulos, navegación, encabezados, pies de página, acceso y documentación editable en Java y la web. Se mantienen los nombres técnicos parqueadero_jc, Parqueadero_jc_web, paquetes, rutas y artefacto Maven para conservar las conexiones y accesos existentes. No hubo modificaciones de datos ni SQL. Las capturas/PDF históricos no se regeneraron en este cambio.

Verificado: compilación Java, sintaxis PHP y ausencia del nombre visible anterior en el código de las pantallas. Reiniciar Java y recargar la web para ver el nombre nuevo.
