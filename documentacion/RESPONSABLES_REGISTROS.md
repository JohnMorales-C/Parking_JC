# Responsables visibles por registro
Implementado en Java y PHP el 15 de noviembre de 2026. Las fichas de clientes, vehículos, espacios, tarifas, métodos de pago, usuarios, incidentes e ingresos/salidas muestran quién creó el registro y quién realizó la última operación: nombre y usuario históricos, perfil al operar, fecha y origen (JAVA, WEB o SQL). En Java se consulta al seleccionar una fila; en la web al abrir Ver o editar una ficha. El cobro histórico de Java también incluye responsables. Usuarios incorpora una ficha Ver de solo lectura en la web.

La consulta se actualiza al abrir o volver a seleccionar el registro; no requiere entrar al módulo Auditoría. No hay actualización automática entre equipos si una ficha permanece abierta: vuelve a abrirla/seleccionarla para consultar cambios nuevos.

El perfil se guarda desde esta mejora y no cambia al renombrar la cuenta o cambiarle el rol. Los eventos anteriores conservan su autor conocido, pero muestran “Sin rol histórico registrado”. Si el registro precede a la auditoría, se indica “Sin responsable registrado”: no se inventan creadores. Las operaciones SQL se identifican como mantenimiento de base de datos.

El Operador puede consultar responsables en sus propios módulos, sin obtener acceso al visor completo ni a los detalles de usuarios, tarifas, espacios o métodos de pago. La consulta no expone contraseñas ni hashes.

SQL: docs/sql/responsables-registros-20261115.sql; actualización registrada en demo/STORED_PROCEDURES.sql y entrega_sql/parqueadero_jc_unificado_estructura.sql. Base actual: 15 tablas, 53 procedimientos, 46 triggers. Respaldo anterior: .backups/unificacion_20261115_122506/.

Verificación: formularios HTTP y Java reales en una copia temporal; creación de Operador y edición de Administrador, conservación del perfil tras cambiar el rol, las ocho fichas, registros antiguos, escape HTML y restricciones. Java compila; ambos archivos SQL completos importan con 53 procedimientos. Pruebas: tmp/probar_responsables.php y ResponsableRegistroIntegracionTest (solo QA). Queda el recorrido visual manual y actualizar el PDF de entrega.
