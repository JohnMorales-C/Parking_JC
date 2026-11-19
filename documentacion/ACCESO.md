## Estado actual - unificación del 12 de noviembre de 2026

Java y PHP utilizan parqueadero_jc, con 13 tablas y 47 procedimientos. Se conservaron exclusivamente los datos y las credenciales del escritorio. Los datos de parqueadero_jc_web no se migraron. Las referencias siguientes a bases separadas y pruebas anteriores son históricas. Ver UNIFICACION.md para la configuración vigente.

# Acceso general y usuarios

## Uso
Abrir http://localhost/Parqueadero_jc_web/ e ingresar con una cuenta de la base web.
Las cuentas se copiaron al separar parqueadero_jc_web de parqueadero_jc. Esta implementación no cambió las contraseñas existentes.
Desde Usuarios y roles, un Administrador puede crear cuentas, editar nombre/usuario, asignar rol, restablecer una contraseña o seleccionar Sin acceso.
Al editar, dejar ambos campos de contraseña vacíos conserva la contraseña actual.
Las cuentas no se eliminan: Sin acceso mantiene sus referencias históricas.
Cambiar mi contraseña pide la contraseña actual y cierra la sesión tras guardar.
Cerrar sesión usa POST protegido con CSRF. El acceso expira tras 30 minutos sin actividad.
Configuración inicial solo está disponible si no hay ningún Administrador habilitado.

## Permisos
| Módulo | Administrador | Operador |
|---|---|---|
| Inicio y contraseña propia | Sí | Sí |
| Clientes y Vehículos | Sí | Sí |
| Ingresos y Salidas | Sí | Sí |
| Incidentes | Sí | Sí |
| Tarifas, Espacios, Métodos de pago | Sí | No |
| Usuarios y roles | Sí | No |

Los permisos se comprueban antes de procesar GET o POST. Ocultar enlaces no es la protección principal.
El responsable del ingreso se toma de la sesión; un ID enviado manualmente se ignora.
Métodos de pago usa la misma sesión general; ya no requiere otro inicio de sesión.
Inicio muestra conteos reales y movimientos recientes; no usa los números de demostración.

## Implementación
- php/login.php, configuracion.php, logout.php, clave.php, usuarios.php, inicio.php.
- php/includes/auth-service.php y web-bootstrap.php.
- Guardas de acceso incorporadas a todos los módulos PHP y a movimientos-bootstrap.php.
- php/includes/pagos-service.php conserva sus operaciones y deja la autenticación a la sesión general.
- assets/usuarios.js: búsqueda del listado.
- Las pantallas funcionales están en php/. .htaccess redirige las direcciones HTML antiguas sin archivos HTML auxiliares.
- .htaccess mantiene la entrada principal y la compatibilidad de rutas antiguas.
- Credenciales de MySQL permanecen fuera de htdocs en C:/xampp/parqueadero-private/database.php.

Se reutilizan sp_login_usuario, sp_estado_acceso, sp_configurar_administrador, sp_listar_usuarios, sp_listar_roles_acceso, sp_guardar_usuario, sp_exigir_administrador y sp_cambiar_clave.
No se crearon o modificaron procedimientos ni tablas de las aplicaciones. Las definiciones ya están en el respaldo SQL de separación.
La base web conserva sus 47 rutinas; Java conserva sus 45 rutinas.
Contraseñas PBKDF2-HMAC-SHA256, sal aleatoria de 16 bytes, 600000 iteraciones y derivación de 32 bytes, compatible con Java.
Sesión regenerada al iniciar/cerrar; cookies HttpOnly y SameSite Strict; CSRF; pausa por sesión después de cinco fallos; revalidación del rol y huella de contraseña en cada petición.
El acceso sigue limitado al equipo local.

## Verificación
Pruebas HTTP con cuentas temporales: acceso anónimo bloqueado en todos los módulos, roles en GET/POST, navegación por rol, creación/edición de cuentas, duplicados, CSRF, validación de contraseña y roles, revocación de cuenta, reset de clave, cambio propio y cierre de sesión.
Entrada de Operador con intento de enviar otro responsable: se guardó el usuario de la sesión.
Último Administrador protegido en esquema temporal independiente mediante copias de las rutinas existentes.
Todos los datos, cuentas y el esquema temporal se retiraron. Checksums de las 13 tablas originales de Java sin cambios.
Sintaxis PHP comprobada. No se hizo nueva revisión visual automatizada.

## Pendiente
El recorrido funcional integrado por Apache está aprobado; ver REVISION_FINAL.md. Queda el recorrido visual y manual con tus cuentas habituales.
