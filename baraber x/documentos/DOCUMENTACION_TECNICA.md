# Documentación técnica de BARBER X

## 1. Propósito y alcance

Esta guía describe la estructura y el funcionamiento del sistema web BARBER X: interfaz, lógica JavaScript, estilos, API en PHP, conexión PDO, base de datos, documentos y recursos multimedia. Sirve como referencia para instalar, mantener y ampliar el proyecto.

El sistema está hecho para ejecutarse en un servidor web PHP con MySQL/MariaDB, como XAMPP. La interfaz se comunica con la API mediante `fetch`; los datos persistentes se almacenan en la base de datos `barber_x`.

## 2. Tecnologías y arquitectura

- **Interfaz:** HTML5 en `index.html`, CSS3 en `css/styles.css` y JavaScript nativo incluido al final de `index.html`.
- **Servidor:** PHP; cada archivo de `api/` atiende una parte de la aplicación y responde principalmente en JSON.
- **Persistencia:** MySQL/MariaDB mediante PDO y sentencias preparadas.
- **Sesión:** sesión nativa de PHP con una cookie `HttpOnly`, `SameSite=Lax` y `Secure` cuando HTTPS está activo.
- **Versión de PHP:** se recomienda PHP 8.x, compatible con la sintaxis moderna utilizada en los endpoints.
- **Servidor de desarrollo previsto:** XAMPP (Apache, PHP y MySQL). La aplicación necesita abrirse por `http://localhost/...`; no funciona como aplicación completa si se abre `index.html` directamente desde el explorador de archivos.
- **Dependencias de frontend:** no se usa un framework JavaScript ni un gestor de paquetes. Los gráficos e ilustraciones principales se generan con SVG desde JavaScript/CSS.

### Flujo general

1. La pantalla inicia en el formulario de acceso de `index.html`.
2. `api/sesion.php` valida el inicio de sesión o el registro y devuelve el usuario y el rol.
3. La interfaz muestra el espacio correspondiente: administración, cliente o barbero.
4. Los módulos solicitan o envían datos a los endpoints de `api/`.
5. Los endpoints validan rol y datos, consultan MySQL con PDO y devuelven el resultado.

## 3. Estructura del proyecto

```text
baraber x/
├── index.html
├── conexionBD.php
├── database.sql
├── favicon.svg
├── api/
│   ├── .htaccess
│   ├── barberos.php
│   ├── citas.php
│   ├── manual.php
│   ├── personas.php
│   ├── reportes.php
│   ├── servicios.php
│   ├── sesion.php
│   └── video.php
├── css/
│   └── styles.css
├── documentos/
│   ├── DOCUMENTACION_TECNICA.md
│   └── PAC.docx
├── anexos/
│   ├── SRS_Barber_X.docx
│   ├── barber_x (1).sql
│   └── Imagen pegada (organizada).png
├── images/manual/
│   ├── paso-1.png ... paso-7.png
└── videos/
    └── tutorial.mp4
```

## 4. Interfaz y lógica del navegador: `index.html`

`index.html` contiene el documento principal, las vistas de los tres roles, los diálogos y el JavaScript de interacción. No hay un archivo JavaScript externo separado.

### Pantallas y módulos

- **Acceso y registro:** `#login-screen`, `#login-form` y `#register-form`.
- **Panel administrativo:** navegación lateral y secciones para inicio, clientes, barberos, citas, servicios, reportes, anexos, PAC, calidad y registros, video y manual.
- **Calidad y registros:** seguimiento de errores de aplicación, sugerencias de clientes, incidencias manuales, estado de resolución, líneas recientes de logs PHP/Apache e indicadores del PAC.
- **Espacio del cliente:** consulta de servicios, reserva e historial de citas, envío de sugerencias, video y manual.
- **Espacio del barbero:** resumen y agenda propia, con filtros para ver el día o la semana.
- **Diálogos:** edición de personas, avisos y confirmaciones de operaciones.
- **Anexos:** pestañas para consultar el SQL en la página, descargar el SRS, ver el diagrama organizado y abrir o descargar esta guía técnica. Los botones de pestañas ocultan el panel no seleccionado y permiten cambiar de opción con teclado.
- **PAC:** el contenido del plan está presentado como secciones HTML. `documentos/PAC.docx` es el archivo Word original que se conserva en el proyecto.

### Grupos de funciones JavaScript

- **Peticiones y utilidades:** `readJsonResponse`, `requestApi`, `requestJson`, `requestSession`, `escapeHtml`, `currency` y `initials`.
- **Navegación y estado:** `showWorkspace`, `showLogin`, `setAdminModule`, `setClientTab`, `showRegisterForm` y `showLoginForm`.
- **Personas y barberos:** `loadPeople`, `render`, `updateSection`, `openModal`, `closeModal`, `loadBarbers`.
- **Servicios:** `loadServices`, `loadAdminServices`, `renderAdminServices`, `resetServiceForm` y `serviceIllustration`.
- **Citas y disponibilidad:** `renderBookingCalendar`, `renderBookingTimes`, `loadBookingSlots`, `loadAppointments`, `loadClientAppointments`, `startBookingReschedule` y `renderAppointments`.
- **Paneles y visualización:** `loadDashboard`, `loadBarberDashboard`, `loadReports`, `loadReportHistory`, `renderActivityChart`, `renderStatusChart`, `renderPopularServices` y `renderUpcomingAppointments`.
- **Contenido de ayuda:** `loadManual` y `loadVideo`.
- **Calidad:** `renderQualityRecords` y `renderServerLogs` presentan los registros e incidencias técnicas; `loadQuality` consulta los datos administrativos.
- **Anexos:** `selectAnnexTab` cambia la pestaña activa y `loadAnnexSql` carga el archivo SQL y presenta errores si el servidor no lo puede leer.

Los formularios usan `fetch` para llamar a PHP. Los datos de personas y servicios se insertan como texto escapado al construir tablas; los errores del servidor se muestran en elementos de alerta.

## 5. Hoja de estilos: `css/styles.css`

La hoja reúne los estilos globales y los componentes de la interfaz. Usa variables CSS para el fondo oscuro, paneles, bordes, tipografía, acentos dorados y estados.

### Áreas principales

- Estructura del panel, navegación lateral, encabezados, perfiles y migas de pan.
- Tarjetas de indicadores, tablas, búsquedas, estados y gráficos SVG.
- Formularios, campos, botones, diálogos y mensajes de error.
- Diseño de acceso, panel de cliente, navegación móvil y panel del barbero.
- Catálogo de servicios, ilustraciones, calendario, franjas y horarios de citas.
- Reportes, historial, video, manual, PAC y pestañas de anexos.
- Imágenes de ayuda y diagrama de base de datos, con límites de ancho para evitar desbordamiento.

Las reglas responsivas principales están en `@media (max-width: 1000px)`, `@media (max-width: 850px)`, `@media (max-width: 620px)` y `@media (max-width: 340px)`. También se respeta `prefers-reduced-motion` para reducir animaciones cuando el sistema del usuario lo solicita.

## 6. API PHP

Todas las rutas viven en `api/`. Las rutas de datos requieren sesión; los permisos concretos dependen del rol. Los endpoints responden con códigos HTTP y, salvo las descargas, JSON con codificación UTF-8. Las escrituras usan consultas preparadas y validan datos en el servidor.

| Archivo / ruta | Métodos y acciones | Función y acceso |
|---|---|---|
| `api/sesion.php` | `GET`; `POST` con `accion=login`, `registro` o `logout` | Consultar la sesión, iniciar o cerrar sesión y registrar clientes. El registro público es solo para clientes. Verifica contraseñas con `password_verify`; las nuevas se guardan usando `password_hash`. |
| `api/personas.php?tipo=clientes\|barberos\|recepcionistas` | `GET`, `POST`, `PUT?id=...`, `DELETE?id=...` | CRUD administrativo de personas. Comprueba campos, longitudes, formato y duplicados. Al crear un barbero también crea su cuenta autorizada dentro de una transacción. |
| `api/barberos.php` | `GET` | Lista barberos activos para usuarios autenticados. |
| `api/servicios.php` | `GET`, `POST`, `PUT?id=...` | Consulta servicios; clientes y barberos reciben solo activos. Solo administración puede crear o modificar servicios. Incluye validación de precio, duración, nombre y estado. |
| `api/citas.php` | `GET`; `GET?accion=horarios&id_barbero=...&fecha=...`; `POST`; `PUT?id=...` | Lista citas según el rol; consulta horarios; permite al cliente reservar, cancelar o reprogramar; permite a administración cambiar estados. Las reservas verifican disponibilidad y se guardan con una transacción. |
| `api/reportes.php` | `GET`; `GET?accion=lista`; `GET?id=...`; `POST` con `accion=crear` | Solo administración. Consolida indicadores, lista hasta 100 reportes guardados, genera un reporte y descarga una hoja compatible con Excel en formato `.xls`. |
| `api/calidad.php` | `GET`, `POST`, `PUT?id=...` | Administración consulta registros, indicadores y logs PHP/Apache, crea errores o sugerencias manuales y actualiza estados. Clientes autenticados pueden enviar sugerencias; no pueden consultar registros ni cambiar estados. |
| `api/calidad_logger.php` | Función interna; no es una ruta HTTP | Registra errores capturados por las APIs en `registro_calidad` y mantiene su escritura en el log PHP. La visualización de logs aplica una sanitización común para ocultar contraseñas y tokens reconocibles. |
| `api/manual.php` | `GET`, `PUT`, `DELETE` | Usuarios autenticados consultan el manual; solo administración puede editarlo o eliminarlo. El contenido se guarda en `manual_usuario`. |
| `api/video.php` | `GET`, `POST` multipart, `DELETE` | Usuarios autenticados consultan el video; solo administración puede cambiar metadatos/archivo o eliminarlo. Acepta MP4 de hasta 220 MB. |

### Permisos importantes

- **Administrador:** gestiona personas, servicios, citas, reportes, calidad, video y manual.
- **Cliente:** consulta servicios activos, reserva y administra únicamente sus propias citas, envía sugerencias y consulta contenido de ayuda.
- **Barbero:** consulta únicamente su agenda; la API oculta datos privados del cliente. El acceso requiere una cuenta autorizada y un perfil activo.
- **Calidad:** la consulta de registros y la lectura de logs del servidor son exclusivas de administración. La API solo devuelve un máximo de 200 registros y hasta 100 líneas recientes por fuente de logs; las rutas locales no se exponen.

### Configuración de cargas

`api/.htaccess` configura límites y tiempos de PHP para recibir videos:

- `upload_max_filesize`: 220 MB.
- `post_max_size`: 225 MB.
- `max_execution_time` y `max_input_time`: 300 segundos.

El servidor Apache debe permitir las directivas de `.htaccess`. La configuración de PHP también debe permitir cargas del tamaño indicado.

## 7. Conexión con la base de datos: `conexionBD.php`

Este archivo crea `$conexion`, un objeto PDO configurado para:

- Host `localhost`, base `barber_x`, usuario local `root` y contraseña vacía en la instalación entregada.
- Conexión `utf8mb4`.
- Errores como excepciones (`PDO::ERRMODE_EXCEPTION`).
- Filas como arreglos asociativos (`PDO::FETCH_ASSOC`).
- Preparaciones nativas (`PDO::ATTR_EMULATE_PREPARES = false`).

En un entorno distinto de desarrollo se deben configurar credenciales propias y limitar su acceso; no se deben publicar credenciales de producción.

## 8. Base de datos: `database.sql`

`database.sql` es el instalador/evolución principal del esquema. Crea o selecciona `barber_x`, define tablas e índices, agrega contenido inicial y contiene una rutina de migración para instalaciones con estructuras anteriores. Conviene respaldar la base de datos antes de ejecutar el script sobre datos existentes: la migración incluye modificaciones y limpieza de cuentas de barbero no autorizadas.

### Tablas y relaciones

| Tabla | Propósito |
|---|---|
| `cliente` | Datos de clientes, correo, hash de contraseña y fecha de registro. |
| `barbero` | Perfil, contacto, especialidad y estado activo del personal. |
| `cuenta_barbero` | Hash y autorización de acceso del barbero; clave foránea al perfil de barbero. |
| `recepcionista` | Datos y estado de recepcionistas. |
| `servicio` | Catálogo de prestaciones, precio, duración y estado. |
| `cita` | Reserva con cliente, barbero, fecha/hora, estado, precio y notas. |
| `cita_servicio` | Detalle que relaciona citas con servicios, cantidades y precio registrado. |
| `manual_usuario` | Título y contenido del manual editable. |
| `video_tutorial` | Título, descripción y nombre del archivo de video. |
| `reporte_generado` | Historial de reportes y copia JSON de los datos utilizados. |
| `registro_calidad` | Errores de aplicación, sugerencias de clientes, registros manuales administrativos y sus estados de seguimiento. |

Relaciones: una cita pertenece a un cliente y un barbero; `cita_servicio` relaciona las citas con sus servicios; `cuenta_barbero` depende del perfil en `barbero`. Hay índices para búsquedas por fecha, barbero/fecha, estado y tablas de detalle.

### Archivo SQL de anexos

`anexos/barber_x (1).sql` es el archivo SQL adicional entregado para consulta o descarga. Se conserva separado de `database.sql`: el último es el script principal del proyecto con sus cambios de esquema; el archivo de anexos es una copia SQL proporcionada como material complementario.

## 9. Documentos

- `documentos/PAC.docx`: Plan de Aseguramiento de Calidad original, con objetivos, alcance, requisitos, matriz de pruebas, roles, gestión de defectos e indicadores. Su contenido también está representado en la sección PAC de la interfaz.
- `anexos/SRS_Barber_X.docx`: especificación de requisitos del sistema (SRS); desde Anexos se puede descargar el archivo original.
- `documentos/DOCUMENTACION_TECNICA.md`: esta guía de arquitectura, código y archivos.

## 10. Imágenes y recursos visuales

- `favicon.svg`: icono vectorial de la pestaña del navegador; muestra la letra **B** sobre el fondo oscuro y el borde dorado de la marca.
- `images/manual/paso-1.png`: pantalla de inicio de sesión.
- `images/manual/paso-2.png`: registro de una cuenta de cliente.
- `images/manual/paso-3.png`: espacio del cliente y servicios.
- `images/manual/paso-4.png`: panel de administración y lista de clientes.
- `images/manual/paso-5.png`: formulario para agregar un barbero.
- `images/manual/paso-6.png`: formulario para editar un cliente.
- `images/manual/paso-7.png`: confirmación de eliminación.

`loadManual()` vincula las capturas con frases concretas del contenido del manual y les asigna texto alternativo. Si se cambia el texto ancla en el manual, se debe actualizar el mapa de imágenes de esa función.

`anexos/Imagen pegada (organizada).png` es el diagrama ordenado del esquema. Representa las diez tablas, las claves primarias y las relaciones de claves foráneas del SQL adjunto; se eliminaron los controles de la captura de phpMyAdmin. La imagen se mantiene dentro del marco de la pestaña Anexos y también se puede descargar.

## 11. Video

- `videos/tutorial.mp4`: video inicial que se muestra en el espacio del cliente y en la edición de administración.
- `database.sql` crea el registro inicial del video en `video_tutorial`.
- `api/video.php` almacena y elimina archivos MP4 y actualiza sus metadatos. Los archivos subidos reciben un nombre aleatorio dentro de `videos/`; la API limita las rutas de archivo aceptadas antes de devolver una URL.
- El reproductor HTML5 está en `index.html`; `loadVideo()` solicita los metadatos a la API y configura el elemento `<video>`.

## 12. Icono: `favicon.svg`

El favicon es SVG embebido directamente en el proyecto: no depende de una imagen externa. El enlace se encuentra en el `<head>` de `index.html`.

## 13. Instalación local en XAMPP

1. Copiar la carpeta del proyecto dentro de `C:\xampp\htdocs\`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Abrir phpMyAdmin e importar `database.sql` para crear o actualizar el esquema `barber_x`. Hacer copia de seguridad antes de aplicar el script a una base con datos.
4. Revisar `conexionBD.php` y cambiar host, base, usuario o contraseña si la instalación local utiliza valores distintos.
5. Abrir `http://localhost/<carpeta-del-proyecto>/index.html`. Las rutas de recursos y API son relativas a la carpeta.
6. Para subir videos, comprobar que Apache permita `.htaccess` y que PHP tenga habilitados los límites indicados en `api/.htaccess`.

## 14. Mantenimiento y ampliaciones

- Al agregar un endpoint, aplicar autenticación, validar el rol en PHP, validar entradas del lado servidor y responder con un código HTTP y JSON claro.
- Mantener en `database.sql` las tablas, índices, claves foráneas, datos iniciales y migraciones necesarias.
- Al modificar rutas de API, actualizar las llamadas `requestJson()`/`requestApi()` y los controles de error en `index.html`.
- Al agregar imágenes del manual, añadir el archivo en `images/manual/` y relacionarlo con su texto ancla en `loadManual()`.
- Al cambiar la identidad visual o los componentes, revisar `css/styles.css` en escritorio y los breakpoints móviles.
- Probar en navegador las rutas que leen/escriben datos y comprobar los permisos con los roles correspondientes.

## 15. Referencias de archivos

- Interfaz: `../index.html`
- Estilos: `../css/styles.css`
- Conexión: `../conexionBD.php`
- Esquema: `../database.sql`
- API: `../api/`
- Documentos y anexos: `./` y `../anexos/`
- Imágenes: `../images/manual/`
- Video: `../videos/tutorial.mp4`
