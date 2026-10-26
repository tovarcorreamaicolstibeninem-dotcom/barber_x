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

INSERT INTO manual_usuario (id_manual, titulo, contenido)
VALUES (1, 'Manual de usuario', 'Bienvenido a BARBER x.\n\nCLIENTES\n1. Consulta los servicios disponibles.\n2. Entra en Agendar cita, selecciona un servicio y fecha y hora.\n3. Consulta el estado de tus citas en la pestaña Mis citas.\n\nADMINISTRACIÓN\nUsa el menú para administrar clientes, barberos, recepcionistas y citas. En Reportes puedes consultar el resumen del negocio. Este manual puede editarse desde el panel de administración.')
ON DUPLICATE KEY UPDATE id_manual = VALUES(id_manual);

INSERT INTO servicio (nombre, descripcion, precio, duracion, activo)
SELECT nuevos.nombre, nuevos.descripcion, nuevos.precio, nuevos.duracion, 1
FROM (
  SELECT 'Corte clásico' AS nombre, 'Corte tradicional con acabado y peinado.' AS descripcion, 25000.00 AS precio, 40 AS duracion
  UNION ALL
  SELECT 'Corte degradado', 'Degradado personalizado con detalle en contornos.', 30000.00, 45
  UNION ALL
  SELECT 'Arreglo de barba', 'Perfilado y arreglo de barba con acabado de navaja.', 18000.00, 30
  UNION ALL
  SELECT 'Corte y barba', 'Servicio completo de corte y arreglo de barba.', 42000.00, 65
) AS nuevos
WHERE NOT EXISTS (
  SELECT 1 FROM servicio existente WHERE existente.nombre = nuevos.nombre
);

DELIMITER $$
CREATE PROCEDURE migrar_esquema_barber_x()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cliente' AND column_name = 'password_hash'
  ) THEN
    ALTER TABLE cliente ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER email;
  END IF;

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
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'cita_servicio' AND column_name = 'precio'
  ) THEN
    ALTER TABLE cita_servicio ADD COLUMN precio DECIMAL(10,2) DEFAULT NULL;
  END IF;
END$$
CALL migrar_esquema_barber_x()$$
DROP PROCEDURE migrar_esquema_barber_x$$
DELIMITER ;
