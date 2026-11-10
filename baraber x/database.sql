CREATE DATABASE IF NOT EXISTS barber_x
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE barber_x;

CREATE TABLE IF NOT EXISTS cliente (
  id_clte INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellido VARCHAR(50) NOT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  fecha_registro DATE DEFAULT CURRENT_DATE,
  PRIMARY KEY (id_clte),
  UNIQUE KEY cliente_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS barbero (
  id_barbero INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellido VARCHAR(50) NOT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  especialidad VARCHAR(100) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_barbero),
  UNIQUE KEY barbero_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cuenta_barbero (
  id_barbero INT NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  autorizada_por_admin TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_barbero),
  CONSTRAINT cuenta_barbero_barbero_fk FOREIGN KEY (id_barbero)
    REFERENCES barbero (id_barbero) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recepcionista (
  id_recepcionista BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellido VARCHAR(50) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  email VARCHAR(100) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_recepcionista),
  UNIQUE KEY recepcionista_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicio (
  id_serv INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  duracion INT UNSIGNED NOT NULL DEFAULT 30,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_serv),
  UNIQUE KEY servicio_nombre_unique (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cita (
  id_cita INT NOT NULL AUTO_INCREMENT,
  id_clte INT NOT NULL,
  id_barbero INT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
  metodo_pago VARCHAR(30) DEFAULT NULL,
  precio DECIMAL(10,2) DEFAULT NULL,
  notas VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id_cita),
  KEY cita_fecha_idx (fecha_hora),
  KEY cita_barbero_fecha_idx (id_barbero, fecha_hora),
  KEY cita_estado_idx (estado),
  CONSTRAINT cita_cliente_fk FOREIGN KEY (id_clte) REFERENCES cliente (id_clte),
  CONSTRAINT cita_barbero_fk FOREIGN KEY (id_barbero) REFERENCES barbero (id_barbero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cita_servicio (
  id_cs INT NOT NULL AUTO_INCREMENT,
  id_cita INT NOT NULL,
  id_serv INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  precio DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (id_cs),
  KEY cita_servicio_cita_idx (id_cita),
  KEY cita_servicio_servicio_idx (id_serv),
  CONSTRAINT cita_servicio_cita_fk FOREIGN KEY (id_cita) REFERENCES cita (id_cita),
  CONSTRAINT cita_servicio_servicio_fk FOREIGN KEY (id_serv) REFERENCES servicio (id_serv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manual_usuario (
  id_manual TINYINT UNSIGNED NOT NULL,
  titulo VARCHAR(120) NOT NULL DEFAULT 'Manual de usuario',
  contenido MEDIUMTEXT NOT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_manual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_tutorial (
  id_video TINYINT UNSIGNED NOT NULL,
  titulo VARCHAR(120) NOT NULL DEFAULT 'Video explicativo',
  descripcion VARCHAR(2000) NOT NULL DEFAULT '',
  archivo VARCHAR(100) DEFAULT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_video)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reporte_generado (
  id_reporte BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  generado_por VARCHAR(120) NOT NULL,
  total_citas INT UNSIGNED NOT NULL DEFAULT 0,
  total_clientes INT UNSIGNED NOT NULL DEFAULT 0,
  ingresos DECIMAL(14,2) NOT NULL DEFAULT 0,
  datos_json LONGTEXT NOT NULL,
  PRIMARY KEY (id_reporte),
  KEY reporte_generado_fecha_idx (generado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO video_tutorial (id_video, titulo, descripcion, archivo)
VALUES (1, 'Video explicativo de BARBER X', 'Conoce las principales opciones de la pagina en este video.', 'tutorial.mp4')
ON DUPLICATE KEY UPDATE id_video = VALUES(id_video);

INSERT INTO manual_usuario (id_manual, titulo, contenido)
VALUES (1, 'Manual de usuario BARBER X', 'BARBER X\nMANUAL DE USUARIO\nSistema web de gestión de barbería\nVersión 1.0 | Octubre de 2026\nGuía para clientes y administradores\n\nAPRENDICEZ:\nMaicol stiben tovar correa\nJefferson valderama\nSteveen Perez\n\n1. Introducción\nBARBER X es una aplicación web para apoyar la gestión básica de una barbería. Permite que los clientes creen una cuenta, inicien sesión y consulten los servicios disponibles. El perfil de administración permite gestionar los registros de clientes, barberos y recepcionistas.\nEste manual explica cómo ingresar al sistema, utilizar sus opciones principales y resolver problemas comunes. La interfaz utiliza un diseño oscuro con detalles dorados y puede adaptarse a pantallas de computador, tableta y celular.\n\n2. Objetivo del manual\nOrientar al usuario para que pueda utilizar BARBER X de forma correcta, desde el registro o inicio de sesión hasta la consulta de servicios y la administración de personas autorizadas.\n\n3. Requisitos para utilizar el sistema\nUn navegador web actualizado, como Google Chrome o Microsoft Edge.\nPara ejecutar el proyecto localmente: un entorno de servidor como XAMPP con Apache, PHP y MySQL habilitados.\nLa base de datos MySQL llamada barber_x y las tablas que utiliza la aplicación.\nLos archivos del proyecto ubicados dentro de la carpeta pública de XAMPP, normalmente htdocs.\nConexión correcta entre PHP y MySQL configurada en conexionBD.php.\nImportante: los archivos entregados incluyen el script database.sql, que crea las tablas recepcionista y cuenta_cliente y agrega servicios de ejemplo. El propio script indica que las tablas cliente, barbero y servicio ya deben existir; por tanto, antes de usarlo debe estar instalada la estructura completa de esas tablas.\n\n4. Cómo iniciar el sistema\nAbra el panel de control de XAMPP.\nInicie los servicios Apache y MySQL.\nCopie la carpeta del proyecto dentro de C:\\\\xampp\\\\htdocs\\\\. Mantenga las rutas relativas de los archivos.\nAbra phpMyAdmin desde http://localhost/phpmyadmin.\nSeleccione o cree la base de datos barber_x e importe el archivo database.sql. Verifique previamente que las tablas cliente, barbero y servicio existan.\nRevise conexionBD.php: el servidor está configurado como localhost, la base de datos como barber_x y el usuario como root. La contraseña está vacía en el archivo entregado; ajústela si su instalación de MySQL utiliza otra.\nAbra el sistema desde una dirección local como http://localhost/nombre-de-la-carpeta/index.html. Reemplace nombre-de-la-carpeta por el nombre real de la carpeta del proyecto.\nNo abra index.html haciendo doble clic desde el explorador de archivos: las funciones PHP y las solicitudes a la API necesitan ejecutarse desde el servidor local.\n\nInicio de sesión\n\n5.1 Ingresar como cliente\nEn la pantalla de bienvenida, escriba su correo electrónico en el campo “Usuario o correo electrónico”.\nEscriba la contraseña registrada.\nPulse “Iniciar sesión”.\nSi los datos son correctos, se abrirá el espacio del cliente.\nSi aparece un mensaje de error, compruebe que el correo y la contraseña estén escritos correctamente. La cuenta de cliente se autentica con el correo y la contraseña que se definieron durante el registro.\n\n5.2 Crear una cuenta de cliente\nEn la pantalla de acceso, pulse “Regístrate aquí”.\nComplete nombre, apellido, correo electrónico, teléfono y contraseña.\nLa contraseña debe tener al menos 8 caracteres.\nPulse “Crear cuenta de cliente”.\nSi el registro es válido, el sistema crea o vincula el registro del cliente y abre su sesión.\nEl correo debe tener un formato válido y no puede estar asociado a otra cuenta. Si el sistema indica que ya existe una cuenta, intente iniciar sesión con ella o utilice otro correo.\n\n5.3 Ingresar como administrador\nEn la pantalla de acceso, el administrador debe escribir el usuario configurado y su contraseña autorizada. El código reconoce el identificador “admin”, pero la contraseña válida depende del hash configurado en sesion.php. Solicite al responsable del proyecto las credenciales correctas; no se debe asumir una contraseña por defecto.\n\nEspacio del cliente\n\n6.1 Consultar servicios\nDespués de iniciar sesión, el cliente ve un saludo y la sección “Nuestros servicios”. Allí se muestran los servicios activos que están guardados en la base de datos, ordenados por nombre. Cada tarjeta presenta el nombre, una descripción, el precio y la duración.\n\n6.2 Servicios de ejemplo\nLos valores anteriores corresponden a los datos de ejemplo incluidos en database.sql. El precio y la duración reales pueden cambiar según los datos vigentes en la base de datos.\nEn los archivos revisados no aparece una opción de reservar citas desde la pantalla del cliente; esta versión permite consultar los servicios, no confirmar una reserva desde esa vista.\n\n7. Panel de administración\nEl panel de administración presenta un menú lateral con tres secciones: Clientes, Barberos y Recepcionistas. Seleccione una sección para ver sus registros. En la parte superior se muestran contadores y un campo de búsqueda.\n\n7.1 Consultar y buscar registros\nSeleccione Clientes, Barberos o Recepcionistas en el menú.\nRevise la lista que aparece en la tabla.\nUse el campo de búsqueda para filtrar por nombre o correo electrónico.\nLos contadores resumen el total y algunos datos de los registros de la sección seleccionada.\n\n7.2 Crear un registro\nPulse “Agregar barbero” o “Agregar recepcionista”, según corresponda.\nComplete nombre, apellido, correo y teléfono.\nPara un barbero, indique también su especialidad.\nCuando esté disponible, seleccione el estado Activo o Inactivo.\nPulse “Guardar”. Si desea salir sin guardar, pulse “Cancelar” o cierre el formulario.\n\n7.3 Editar un registro\nBusque a la persona en la tabla.\nPulse el botón con el símbolo de lápiz en su fila.\nModifique los datos necesarios.\nGuarde los cambios y compruebe que la lista se actualice.\n\n7.4 Eliminar un registro\nLocalice el registro que desea retirar.\nPulse el botón de eliminar (símbolo de papelera o borrado) en la fila correspondiente.\nConfirme la acción si la interfaz solicita confirmación.\nAdvertencia: eliminar puede ser una acción irreversible y podría afectar información relacionada. Verifique cuidadosamente el registro antes de continuar.\n\n7.5 Estados y validaciones\nLos campos nombre, apellido, teléfono y correo son obligatorios en el formulario de administración.\nEl correo debe ser válido y no debe repetirse dentro de la misma categoría.\nLos barberos requieren una especialidad.\nLos registros de barberos y recepcionistas pueden marcarse como activos o inactivos.\nSolo una sesión con rol de administrador puede crear, consultar, editar o eliminar registros desde el módulo de administración.\n\n8. Cerrar sesión\nPulse “Cerrar sesión” en la parte superior de la pantalla.\nEl sistema termina la sesión actual.\nPara volver a utilizar el sistema, inicie sesión de nuevo.\nCierre sesión cuando termine de utilizar el sistema, especialmente si comparte el computador con otras personas.\n\n9. Solución de problemas\n\n10. Recomendaciones de uso y seguridad\nNo comparta su contraseña ni utilice credenciales de administrador en cuentas de uso común.\nUse contraseñas de al menos 8 caracteres y evite reutilizar contraseñas personales.\nMantenga una copia de seguridad de la base de datos antes de modificar tablas o eliminar registros.\nRegistre información real, completa y actualizada.\nEn un despliegue público, cambie las credenciales de ejemplo, configure una contraseña segura para MySQL y utilice HTTPS.\nSi ocurre un error, anote el mensaje que aparece y comuníquelo al encargado del sistema.\n\n11. Alcance de la versión documentada\nEste manual se elaboró a partir de los archivos entregados: index.html, styles.css, conexionBD.php, database.sql, personas.php, servicios.php y sesion.php. La versión documentada incluye registro e inicio de sesión de clientes, consulta de servicios y administración de clientes, barberos y recepcionistas.\nLos archivos proporcionados no muestran un flujo funcional para que el cliente reserve una cita ni un módulo de pagos. Aunque el proyecto está orientado a la gestión de una barbería y la base de datos hace referencia a citas, estas funciones no se describen como acciones disponibles en la interfaz revisada.\n\n12. Glosario\nAdministrador: Usuario autorizado para gestionar los registros del sistema.\nCliente: Persona que crea una cuenta e ingresa para consultar los servicios.\nBarbero: Persona que presta los servicios de barbería y puede tener una especialidad registrada.\nRecepcionista: Integrante del personal registrado para apoyar la recepción.\nServicio activo: Servicio que está habilitado para aparecer en la consulta del cliente.\nBase de datos: Lugar donde se almacenan los registros que utiliza la aplicación.\nSesión: Estado temporal que permite reconocer al usuario después de iniciar sesión.')
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), contenido = VALUES(contenido);

UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Corte clásico a tijera o máquina'
SET legacy.activo = 0
WHERE legacy.nombre = 'Corte clásico' AND actual.id_serv IS NOT NULL AND actual.activo = 1;
UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Corte clásico a tijera o máquina'
SET legacy.nombre = 'Corte clásico a tijera o máquina'
WHERE legacy.nombre = 'Corte clásico' AND actual.id_serv IS NULL;

UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Corte fade (degradado)'
SET legacy.activo = 0
WHERE legacy.nombre = 'Corte degradado' AND actual.id_serv IS NOT NULL AND actual.activo = 1;
UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Corte fade (degradado)'
SET legacy.nombre = 'Corte fade (degradado)'
WHERE legacy.nombre = 'Corte degradado' AND actual.id_serv IS NULL;

UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Perfilado y diseño de barba'
SET legacy.activo = 0
WHERE legacy.nombre = 'Arreglo de barba' AND actual.id_serv IS NOT NULL AND actual.activo = 1;
UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Perfilado y diseño de barba'
SET legacy.nombre = 'Perfilado y diseño de barba'
WHERE legacy.nombre = 'Arreglo de barba' AND actual.id_serv IS NULL;

UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Afeitado clásico con navaja'
SET legacy.activo = 0
WHERE legacy.nombre = 'Arreglo de barba con navaja' AND actual.id_serv IS NOT NULL AND actual.activo = 1;
UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Afeitado clásico con navaja'
SET legacy.nombre = 'Afeitado clásico con navaja'
WHERE legacy.nombre = 'Arreglo de barba con navaja' AND actual.id_serv IS NULL;

UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Corte + barba'
SET legacy.activo = 0
WHERE legacy.nombre = 'Corte y barba' AND actual.id_serv IS NOT NULL AND actual.activo = 1;
UPDATE servicio legacy
LEFT JOIN servicio actual ON actual.nombre = 'Corte + barba'
SET legacy.nombre = 'Corte + barba'
WHERE legacy.nombre = 'Corte y barba' AND actual.id_serv IS NULL;

UPDATE servicio
SET nombre = 'Corte clásico (registro anterior)', activo = 0
WHERE HEX(nombre) = '436F72746520636CE2949CC3AD7369636F';

INSERT INTO servicio (nombre, descripcion, precio, duracion, activo)
VALUES
  ('Corte clásico a tijera o máquina', 'Corte tradicional personalizado a tijera o máquina, con laterales, nuca y contornos definidos.', 25000.00, 40, 1),
  ('Corte fade (degradado)', 'Degradado bajo, medio o alto integrado con el largo superior; incluye perfilado de patillas y nuca.', 30000.00, 45, 1),
  ('Perfilado y diseño de barba', 'Recorte y diseño de barba según el rostro, con definición de mejillas, cuello y contornos.', 18000.00, 30, 1),
  ('Afeitado clásico con navaja', 'Preparación de la piel, espuma y afeitado preciso con navaja para un acabado suave y definido.', 20000.00, 35, 1),
  ('Corte + barba', 'Servicio combinado de corte personalizado y perfilado de barba, con acabado limpio en los contornos.', 42000.00, 65, 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

UPDATE manual_usuario
SET contenido = REPLACE(
  REPLACE(
    contenido,
    'ordenados por nombre. Cada tarjeta presenta el nombre, una descripción, el precio y la duración.',
    'presentados en el mismo orden del formulario de citas. Cada tarjeta muestra el nombre, la descripción, el precio y la duración.'
  ),
  'En los archivos revisados no aparece una opción de reservar citas desde la pantalla del cliente; esta versión permite consultar los servicios, no confirmar una reserva desde esa vista.',
  'Para agendar una cita, el cliente elige un servicio activo, un barbero disponible, una fecha y una hora disponible. El sistema verifica el horario del barbero y confirma si la reserva se realizó.'
)
WHERE id_manual = 1;

UPDATE manual_usuario
SET contenido = REPLACE(
  REPLACE(
    contenido,
    'Guía para clientes y administradores',
    'Guía para clientes, barberos y administradores'
  ),
  '5.2 Crear una cuenta de cliente',
  '5.2 Crear una cuenta de cliente\nPara crear una cuenta de barbero, el administrador debe registrar primero al barbero con su correo en la sección Barberos. En la pantalla de registro, seleccione Barbero, escriba ese correo y defina una contraseña de al menos 8 caracteres. La cuenta quedará asociada al registro y solo podrá ver sus propias citas, estadísticas y agenda diaria o semanal.\n\n5.2 Crear una cuenta de cliente'
)
WHERE id_manual = 1;

UPDATE manual_usuario
SET contenido = REPLACE(
  contenido,
  'Para crear una cuenta de barbero, el administrador debe registrar primero al barbero con su correo en la sección Barberos. En la pantalla de registro, seleccione Barbero, escriba ese correo y defina una contraseña de al menos 8 caracteres. La cuenta quedará asociada al registro y solo podrá ver sus propias citas, estadísticas y agenda diaria o semanal.',
  'Solo el administrador puede crear una cuenta de barbero. En la sección Barberos, registre sus datos y defina una contraseña de al menos 8 caracteres; para cambiarla, edite el registro y escriba una nueva contraseña. Entregue la contraseña directamente al barbero. La cuenta solo podrá ver sus propias citas, estadísticas y agenda diaria o semanal.'
)
WHERE id_manual = 1;

DELIMITER $$
CREATE PROCEDURE migrar_esquema_barber_x()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cliente' AND column_name = 'password_hash'
  ) THEN
    ALTER TABLE cliente ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER email;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cuenta_barbero' AND column_name = 'autorizada_por_admin'
  ) THEN
    ALTER TABLE cuenta_barbero
      ADD COLUMN autorizada_por_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
  END IF;
  DELETE FROM cuenta_barbero WHERE autorizada_por_admin = 0;

  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'cuenta_cliente'
  ) THEN
    UPDATE cliente c
    INNER JOIN cuenta_cliente cc ON cc.id_clte = c.id_clte
    SET c.email = cc.email, c.password_hash = cc.password_hash
    WHERE c.password_hash IS NULL;
    DROP TABLE cuenta_cliente;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cita' AND column_name = 'precio'
  ) THEN
    ALTER TABLE cita ADD COLUMN precio DECIMAL(10,2) DEFAULT NULL;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cita' AND column_name = 'notas'
  ) THEN
    ALTER TABLE cita ADD COLUMN notas VARCHAR(500) DEFAULT NULL;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'cita' AND index_name = 'cita_barbero_fecha_idx'
  ) THEN
    CREATE INDEX cita_barbero_fecha_idx ON cita (id_barbero, fecha_hora);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cita_servicio' AND column_name = 'precio'
  ) THEN
    ALTER TABLE cita_servicio ADD COLUMN precio DECIMAL(10,2) DEFAULT NULL;
  END IF;
END$$
CALL migrar_esquema_barber_x()$$
DROP PROCEDURE migrar_esquema_barber_x$$
DELIMITER ;
