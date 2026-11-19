# Tarifas vigentes y cobros históricos

## Regla vigente: HORA uniforme y períodos con adicional

HORA solicita únicamente Precio por hora. Se cobra proporcionalmente por minutos completos, sin bloque inicial ni importe adicional diferente. Con 2000 por hora: 30 minutos cuestan 1000; 60, 2000; 90, 3000; 120, 4000. Se redondea al centavo con HALF_UP. Menos de un minuto computa cero minutos; no se añadió una hora mínima ni redondeo a hora iniciada.

DÍA incluye 24 horas desde el ingreso; MES incluye 30 días completos (720 horas), independientemente de la duración del mes calendario. Se cobra el precio base del período y, una vez superado, la hora adicional proporcional a los minutos excedidos. No se vuelve a sumar otro día o mes base: el excedente es continuo.

HORA solo muestra Precio por hora. DÍA y MES muestran Precio del período y Hora adicional. Ninguna modalidad pide Horas base. El adicional admite 0; en ese caso no se suman cargos por excedentes. Las tarifas existentes conservan sus importes: el administrador debe editar el adicional si desea cobrarlo.

## Vigencia y edición

Toda tarifa nueva queda VIGENTE automáticamente y caduca la vigente anterior del mismo tipo de vehículo y modalidad. Crear Carro/HORA de 2027 reemplaza a Carro/HORA de 2026 al guardar. Otros tipos/modalidades no se caducan. El año no provoca caducidad por calendario. Sigue habiendo una vigente por grupo, incluso entre años.

El Administrador puede editar el precio base, la hora adicional de DÍA/MES, descripción y vigencia, y caducar manualmente. Se conserva la reactivación anterior. Si la tarifa tiene cobros, el año, tipo de vehículo y modalidad están protegidos; los precios se pueden cambiar porque cada cobro conserva su propia instantánea. Una tarifa utilizada no se elimina.

## Confirmar una salida

Java, PHP y MySQL calculan el mismo importe. Al confirmar se vuelve a comprobar la vigencia, el método de pago activo y la cotización. Si cambió el precio, hay que calcular de nuevo. El cobro, cierre del ingreso y liberación del espacio se guardan en la misma transacción.

## Histórico

El total y las condiciones utilizadas se guardan en cobro y no se recalculan posteriormente. Se conserva tarifa/código/año, tipo de vehículo, placa, modalidad, precio, minutos, método y responsable de salida. La auditoría identifica al usuario y aplicación que creó o modificó registros.

La columna cobro.regla_calculo distingue ANTERIOR (cobros previos a la migración), POR_HORA (precio uniforme nuevo) FIJO (DÍA/MES antes de incorporar adicionales), DIA24_EXTRA (24 horas incluidas) y MES30_EXTRA (30 días incluidos). El detalle de una salida anterior HORA muestra su bloque inicial y adicional históricos; el nuevo muestra Precio por hora aplicado. Un cobro anterior de 7000 sigue en 7000 aunque su tarifa se edite o se reemplace. No se reescribieron precios ni totales de cobros anteriores.

El campo SQL horas_base se conserva por compatibilidad y las altas/ediciones nuevas lo normalizan a 1; no se solicita ni define la duración del período. valor_hora_adicional se utiliza en DÍA/MES y se fuerza a 0 en HORA. Cada cobro guarda el precio base, adicional, minutos y regla aplicada; el detalle muestra el período incluido y los minutos adicionales de los cobros nuevos.

## Instalación y verificación

Base local compartida parqueadero_jc: 15 tablas, 54 procedimientos y 46 triggers. Las aplicaciones siguen trabajando localmente. No se utilizan las copias remotas incompletas.

Migraciones actuales: docs/sql/hora-uniforme-20261115.sql agrega la identificación de la regla histórica; después docs/sql/dia-mes-adicional-20261115.sql actualiza tres procedimientos para los adicionales de períodos. Las definiciones actuales están en demo/STORED_PROCEDURES.sql y entrega_sql/parqueadero_jc_unificado_estructura.sql. Los SQL de estructura contienen DROP y no deben importarse sobre datos operativos. Las migraciones son secuenciales y no deben repetirse sin revisión.

Antes de aplicar se comprobó que no había tarifas HORA existentes: no fue necesario convertir importes ni caducar tarifas previas. Respaldo: .backups/unificacion_20261115_165552/. Se verificó que todas las columnas y filas anteriores de las 15 tablas permanecieran iguales después de agregar la columna de regla.

Prueba aislada: tmp/probar_hora_uniforme.php y HoraUniformeIntegracionTest. Se contrastaron Java/PHP/MySQL para 0, 1, 30, 60, 90, 120 y 121 minutos; guardado HTTP con un único precio; edición, rechazo de cotización obsoleta; conservación y consulta de un cobro creado bajo la regla anterior; DÍA/MES históricos fijos y auditoría con regla de cálculo. Se verificaron compilación, sintaxis PHP, HTTP por rol e importación de los SQL.

## Demostración

1. Reiniciar Java y recargar la web con Ctrl+F5.
2. Como Administrador, crear HORA con precio 2000 para un tipo de vehículo.
3. Registrar una entrada y consultar su cotización: el total crece proporcionalmente por minutos.
4. Confirmar una salida y consultar el histórico.
5. Editar el precio y comprobar que el cobro anterior conserva su total y precio aplicado.

## Ejemplos de adicionales

DÍA con base 25000 y adicional 2000/h: 24 horas cuestan 25000; 24 horas y media cuestan 26000; 25 horas cuestan 27000. MES con base 300000 y adicional 2000/h: 30 días cuestan 300000; 30 días y media hora cuestan 301000.

Prueba actual: tmp/probar_adicionales_periodo.php y AdicionalPeriodoIntegracionTest. Java/PHP/MySQL coinciden justo antes, en y después de 24 horas/30 días, incluidos excedentes fraccionarios. Se probaron alta/edición por HTTP y Java, visibilidad del adicional, cotización obsoleta, validación y conservación exacta de un cobro previo FIJO y otro con adicional tras editar la tarifa.

Respaldo previo a adicionales: .backups/unificacion_20261115_171333/. La migración no cambia tablas ni registros existentes; solo modifica tres procedimientos. Se comprobó el contenido anterior de las 15 tablas sin cambios. Reiniciar Java y recargar la web con Ctrl+F5.
