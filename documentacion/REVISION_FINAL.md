## Estado actual - unificación del 12 de noviembre de 2026

Java y PHP utilizan parqueadero_jc, con 13 tablas y 47 procedimientos. Se conservaron exclusivamente los datos y las credenciales del escritorio. Los datos de parqueadero_jc_web no se migraron. Las referencias siguientes a bases separadas y pruebas anteriores son históricas. Ver UNIFICACION.md para la configuración vigente.

# Revisión funcional — 3 de noviembre de 2026

Resultado: recorrido completo por Apache aprobado en parqueadero_jc_web.

## Verificado
- Inicio de sesión de Administrador y Operador.
- Once páginas: respuesta, título, enlaces locales, formularios con CSRF y archivos CSS/JavaScript.
- Cliente y vehículo creados desde formularios reales.
- Espacio, tarifa y método de pago creados como Administrador.
- Entrada como Operador, espacio compatible y responsable automático de la sesión.
- Incidente asociado a la visita.
- Tarifa y método disponibles en Salidas, cotización y confirmación.
- Importe 5123.45 conservado en el cobro, tarifa/método registrados y espacio liberado.
- Historial e incidente consultables después de la salida.
- Bloqueo de eliminación de tarifa y espacio con movimientos.
- Acceso administrativo rechazado al Operador y cierre de sesión efectivo.

Todas las cuentas y entidades de prueba se eliminaron al terminar. Los checksums de las 13 tablas de parqueadero_jc (Java) no cambiaron.
No se modificaron tablas ni procedimientos.

## Correcciones
Inicio, Usuarios y Cambiar contraseña mostraban Clientes en la ruta de navegación; se corrigieron sus etiquetas y descripciones.
Se retiraron descripciones de demostración en Espacios y Métodos de pago.

## Prueba automatizada retirada
La carpeta de pruebas fue retirada del proyecto por solicitud del usuario. Se conserva arriba el resultado histórico de las comprobaciones realizadas.

## Límite de la revisión
El host de automatización del navegador no estaba disponible. No se comprobaron visualmente alineación, tamaños móviles, foco o comportamiento de los controles JavaScript en un navegador real.
La prueba HTTP no equivale a una revisión visual ni a certificar ausencia de todos los errores.

## Siguiente comprobación manual
1. Abrir http://localhost/Parqueadero_jc_web/ e iniciar sesión.
2. Recorrer el menú como Administrador y después como Operador.
3. Revisar ventanas pequeñas, tablas desplazables, búsquedas y confirmaciones.
4. Comprobar que los textos y tamaños sean cómodos en tu pantalla habitual.
