# Parking JC web

Aplicación PHP, HTML, CSS y JavaScript con MySQL.

## Iniciar

1. Activar Apache y comprobar que MySQL esté disponible.
2. Abrir http://localhost/Parqueadero_jc_web/.
3. Iniciar sesión con una cuenta de la base web.

La web y la aplicación Java comparten `parqueadero_jc`. Se conservan únicamente los datos y las cuentas del escritorio. Los datos de la antigua base web no se migraron.
La conexión privada está fuera de htdocs, en `C:\xampp\parqueadero-private\database.php`.

## Carpetas

- `php/`: pantallas funcionales y acciones del servidor.
- `php/includes/`: conexión, sesiones, permisos y servicios de los módulos.
- Las pantallas usan HTML dentro de archivos PHP; las direcciones HTML anteriores se redirigen directamente desde .htaccess.
- `assets/`: estilos y scripts utilizados por las pantallas.
- `.htaccess`: entrada del sitio y compatibilidad de rutas. El archivo del mismo nombre en php/includes protege esa carpeta.

## Módulos

Inicio, Clientes, Vehículos, Ingresos y Salidas, Tarifas, Espacios, Métodos de pago, Incidentes y Usuarios y roles, con inicio de sesión y cambio de contraseña.

## Documentación

- [Guía de uso](GUIA_USO.md).
- [Acceso y permisos](ACCESO.md).
- [Detalles del CRUD](README-CRUD.md).
- [Resultado de las pruebas y revisión pendiente](REVISION_FINAL.md).

El generador inicial `build.ps1` fue retirado porque las pantallas PHP actuales ya no dependen de él.

Auditoría compartida de cambios: disponible para consulta del Administrador en ambas aplicaciones. Guía: [AUDITORIA.md](AUDITORIA.md). La base actual tiene 15 tablas, 52 procedimientos y 46 triggers.

Tarifas: una vigente por tipo y modalidad hasta reemplazo. Cobros históricos inmutables y consulta en Java/PHP. Guía: [TARIFAS_Y_COBROS.md](TARIFAS_Y_COBROS.md).
