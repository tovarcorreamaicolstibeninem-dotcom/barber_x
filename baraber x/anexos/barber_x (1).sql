-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-11-2026 a las 21:06:59
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `barber_x`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `barbero`
--

CREATE TABLE `barbero` (
  `id_barbero` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `barbero`
--

INSERT INTO `barbero` (`id_barbero`, `nombre`, `apellido`, `telefono`, `email`, `especialidad`, `activo`) VALUES
(2, 'maicol', 'adjda', '121212', 'dadada@gmail.com', 'dcss', 1),
(3, 'tigre', 'mayor', '123424234234', 'dasfsf@gmail.com', 'de todo', 1),
(4, 'steveen', 'perez', '1234567890', 'st@gmail.com', 'de todo', 1),
(5, '123', '123', '123', '123@gmail.com', '123', 1),
(6, 'Barbero', 'Prueba', '0000000000', 'barbero.prueba.20261105.1123@barberx.local', 'Servicios generales', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita`
--

CREATE TABLE `cita` (
  `id_cita` int(11) NOT NULL,
  `id_clte` int(11) NOT NULL,
  `id_barbero` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `estado` varchar(20) DEFAULT 'Pendiente',
  `metodo_pago` varchar(30) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `notas` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cita`
--

INSERT INTO `cita` (`id_cita`, `id_clte`, `id_barbero`, `fecha_hora`, `estado`, `metodo_pago`, `precio`, `notas`) VALUES
(1, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(2, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(3, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(4, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(5, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(6, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(7, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(8, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(9, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(10, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(11, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(12, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(13, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(14, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(15, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, 'hola'),
(16, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, NULL),
(17, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, NULL),
(18, 15, 2, '2026-10-27 18:58:00', 'Pendiente', NULL, 18000.00, NULL),
(19, 15, 2, '2026-10-27 18:58:00', 'Cancelada', NULL, 18000.00, NULL),
(20, 15, 2, '2026-10-27 18:06:00', 'Pendiente', NULL, 18000.00, NULL),
(21, 15, 2, '2026-10-27 18:06:00', 'Pendiente', NULL, 18000.00, NULL),
(22, 17, 2, '2026-10-28 11:10:00', 'Cancelada', NULL, 25000.00, 'corte en v'),
(23, 18, 2, '2026-10-30 03:10:00', 'Completada', NULL, 25000.00, 'corte en v'),
(24, 24, 2, '2026-10-30 08:27:00', 'Pendiente', NULL, 25000.00, 'ajsjcka'),
(25, 28, 2, '2026-10-29 23:43:00', 'Pendiente', NULL, 18000.00, 'hola'),
(26, 30, 2, '2026-10-30 13:45:00', 'Pendiente', NULL, 20000.00, 'hola'),
(27, 30, 2, '2026-10-30 14:30:00', 'Pendiente', NULL, 20000.00, 'hhola'),
(28, 30, 2, '2026-10-30 18:15:00', 'Pendiente', NULL, 18000.00, 'hola'),
(29, 30, 2, '2026-11-03 15:15:00', 'Pendiente', NULL, 25000.00, 'hola'),
(30, 30, 2, '2026-11-03 16:00:00', 'Pendiente', NULL, 20000.00, 'dsdgdthyjhg'),
(31, 30, 2, '2026-11-03 18:15:00', 'Cancelada', NULL, 25000.00, 'dgshfjgh'),
(32, 30, 2, '2026-11-04 07:00:00', 'Completada', NULL, 42000.00, 'jadejsr'),
(33, 30, 3, '2026-11-04 18:15:00', 'Cancelada', NULL, 25000.00, 'adighifjgo'),
(34, 32, 5, '2026-11-10 07:45:00', 'Completada', NULL, 30000.00, 'hols'),
(35, 33, 5, '2026-11-10 07:00:00', 'Pendiente', NULL, 30000.00, 'hols');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita_servicio`
--

CREATE TABLE `cita_servicio` (
  `id_cs` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `id_serv` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 1,
  `precio` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cita_servicio`
--

INSERT INTO `cita_servicio` (`id_cs`, `id_cita`, `id_serv`, `cantidad`, `precio`) VALUES
(1, 1, 3, 1, 18000.00),
(2, 2, 3, 1, 18000.00),
(3, 3, 3, 1, 18000.00),
(4, 4, 3, 1, 18000.00),
(5, 5, 3, 1, 18000.00),
(6, 6, 3, 1, 18000.00),
(7, 7, 3, 1, 18000.00),
(8, 8, 3, 1, 18000.00),
(9, 9, 3, 1, 18000.00),
(10, 10, 3, 1, 18000.00),
(11, 11, 3, 1, 18000.00),
(12, 12, 3, 1, 18000.00),
(13, 13, 3, 1, 18000.00),
(14, 14, 3, 1, 18000.00),
(15, 15, 3, 1, 18000.00),
(16, 16, 3, 1, 18000.00),
(17, 17, 3, 1, 18000.00),
(18, 18, 3, 1, 18000.00),
(19, 19, 3, 1, 18000.00),
(20, 20, 3, 1, 18000.00),
(21, 21, 3, 1, 18000.00),
(22, 22, 5, 1, 25000.00),
(23, 23, 5, 1, 25000.00),
(24, 24, 5, 1, 25000.00),
(25, 25, 6, 1, 18000.00),
(26, 26, 3, 1, 20000.00),
(27, 27, 3, 1, 20000.00),
(28, 28, 6, 1, 18000.00),
(29, 29, 5, 1, 25000.00),
(30, 30, 3, 1, 20000.00),
(31, 31, 5, 1, 25000.00),
(32, 32, 4, 1, 42000.00),
(33, 33, 1, 1, 25000.00),
(34, 34, 2, 1, 30000.00),
(35, 35, 2, 1, 30000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_clte` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_clte`, `nombre`, `apellido`, `telefono`, `email`, `password_hash`, `fecha_registro`) VALUES
(6, 'maik', 'marica', '313131332', 'maik@gmail.com', '$2y$10$MB1QRCaG01QB0lOvGEeBReYbhOvynX8ZVUWZ0EHIWKhMMzwMAXGdO', '2026-10-22 14:39:13'),
(8, 'asda', 'asd', '12343234234234', 'asdasd@gmail.com', '$2y$10$6a1ubm8v/xQ3BsjHmWCeA.b.cCkJi4K9DcGMuywoh0LpY5jfqEr5e', '2026-10-26 11:27:23'),
(9, 'xcv', 'cvbvc', '12343234234234', 'asdasvxcd@gmail.com', '$2y$10$NAMk99YHCiom4Agorh07XeaBiJUxVy9oiEbjIkIis/nna9B5avKVq', '2026-10-26 12:35:45'),
(10, 'xcvdf', 'cvbvcdsf', '12343234234234', 'asdasdsfvxcd@gmail.com', '$2y$10$c9bU2H7LhTm8NLNw0k0I8.QwNmjmDV6OKsDdZpGzwQYtpGTMkR/fW', '2026-10-26 12:37:26'),
(12, 'jaime', 'barreiro', '12345678', 'jaima@gmail.com', '$2y$10$5G.3JHg3NpPq4cO17U45XuABYQopxp8iVsq3T4m77TB/V/m0QFhM.', '2026-10-26 13:57:07'),
(13, 'jaime', 'barreiro', '12345678', 'jaime@gmail.com', '$2y$10$pTZWKcMYXkcMKpAlTrv5EOqbKjgEuGyiScs7kskKYAYvG.Df5HEaW', '2026-10-26 13:59:46'),
(15, 'jefferson', 'murcia', '3112321322', 'jefferson@gmail.com', '$2y$10$BmBOv8UT.QdhQoh4fU7cx.749KorRtyymZGcAdYBExDLrncvZQ.n2', '2026-10-26 19:23:03'),
(16, 'maicol', 'tovar', '3243214q3', 'maicol.tovarco@inemneiva.edu.co', '$2y$10$ln7eTHgLCDZipl6W0m2uquoowyT2.oS06ijG9z/pN0ypU.MuLLtwy', '2026-10-27 12:03:30'),
(17, 'maicol', 'sriben', '3243214q3', 'maik231@gmail.com', '$2y$10$Cg4hSw3U5QET8hn1SYffkOcfPQW.9lyJKbfMGPhoP1FCeU27KdW0.', '2026-10-27 12:09:58'),
(18, 'stiben', 'correa', '3243214q3', 'stiben12@gmail.com', '$2y$10$hdqaUt9a7/8uaDoUOzq7Leaqduxbq6q88csGHQSqC/Em.L8f0hP1e', '2026-10-27 12:13:55'),
(19, 'pene', 'correa', '3243214q3', 'spene12@gmail.com', '$2y$10$ArZF7aMNj5u/ByreCh6mDuPHG4upkMNnQC3nFw8EiiPnReW0jR6IW', '2026-10-27 12:30:42'),
(20, 'nicolas', 'tovar', '12345678', 'nicolas@gmail.com', '$2y$10$bxr306Ha37CYzD.wORwGTOxtG1AOyIqlYIzan1WLMd18Bbtlhl1Ty', '2026-10-27 12:42:56'),
(21, 'nicolas', 'tovar', '12345678', 'maicol@gmail.com', '$2y$10$ivI8RSGuK7R4lpFXpudbFelXfiosPLu95GsBEhaS2VeY6ibjxETbi', '2026-10-27 12:51:20'),
(22, 'stibenn', 'tovar', '554548445', 'tovar@gmail.com', '$2y$10$aLQEvlYa0SaK9apyjzzgtueH/r4YaAA9CUCvGn38qMRbTJr4VGHHC', '2026-10-27 13:48:08'),
(23, 'maicol', 'correa', '3243214q3', 'tovarcorreamaicolstiben.inem@gmail.com', '$2y$10$5IbDFeYKMEfYVef8kMm3DOAExoaDYSwnQpWbhXMqW/Qj0Lg3HjNUm', '2026-10-27 16:07:52'),
(24, 'maicol', 'correa', '3243214q3', 'jhonatantovar.88@gmail.com', '$2y$10$ng04Uj.iLrZqKiuDsVUQcuCP7PD.T44o2cfLWBt3LLnUORzKMqF2C', '2026-10-29 11:24:56'),
(25, 'maicol', 'correa', '121212', 'jessicatatianacorreacastro@gmail.com', '$2y$10$kQO026D4hCWT4zyD0KldsOfLHqJLfvridO8Mv5PfjdkO1c1YEDc8W', '2026-10-29 12:43:46'),
(26, 'maicol', 'correa', '3243214q3', 'maicoql@gmail.com', '$2y$10$EUPZpoZwWmbgWeySnb0ZKuGw.1xSgZ8trs8wtK.dgSrVZj5NpMBV2', '2026-10-29 14:33:39'),
(27, 'Ana', 'García', '123456', 'ana@ejemplo.com', '$2y$10$K1zBgqxWGdSJOyMlBHykae37JjLExotLbi/UN5PhWX8ssqQZ6A/nS', '2026-10-29 15:35:02'),
(28, 'stiben', 'correa', '3243214q3', 'holao@gmail.com', '$2y$10$3Z5iQQv5Epx9/DdQ1NeFXeQwVnb9C8z/iblU0pZJFLaiCu4TiOMXS', '2026-10-29 15:59:53'),
(29, 'stiben', 'correa', '3243214q3', 'holkjkohjao@gmail.com', '$2y$10$WHcJIF5eZcyKhln4IHQCLeDaU52U8TUMETNTpw5u0gftXrHAG0GRW', '2026-10-29 16:22:04'),
(30, 'stiben', 'correa', '3243214q3', 'ssasahjao@gmail.com', '$2y$10$Nv1a5UDm7Jzyp5FY0BxL8OxdtO8zS59njxnRq3.qvkRJR0eFDRF6q', '2026-10-30 01:54:39'),
(31, 'jajajaja', 'jejeje', '544566', 'jiji@gmail.com', '$2y$10$3l1LEy8EyOs.HEa22a7zauSGKYYtZOSsp64vutbu7W5J.YBoyMv/G', '2026-11-03 13:56:59'),
(32, 'jajajaja', 'jejeje', 'admin', 'jippp@gmail.com', '$2y$10$8vfQythHtCZpoCpvMny.QuS6CbPvjXDTSGVWHt0CKl8G.guKB99XS', '2026-11-09 20:00:39'),
(33, 'jajajaja', 'jejeje', 'admin', 'll@gmail.com', '$2y$10$GolWq2TRidce6HA746hZr.aFIGz/ce8bS4XBoDlmYQQ1SPp6RV8NS', '2026-11-09 20:03:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta_barbero`
--

CREATE TABLE `cuenta_barbero` (
  `id_barbero` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `autorizada_por_admin` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuenta_barbero`
--

INSERT INTO `cuenta_barbero` (`id_barbero`, `password_hash`, `autorizada_por_admin`, `creado_en`) VALUES
(2, '$2y$10$0kV3q5MIIys1UwPJS/DgxeBxJjiGBOZinjcwHnxoIsZFGZp0LHlPy', 1, '2026-11-05 16:05:29'),
(3, '$2y$10$cItTXN/.YjToL2zXgvR6meIJIk.CfoP5dj0A25FeHq0wO0NfPWblS', 1, '2026-11-05 16:05:23'),
(4, '$2y$10$GXtk3l68EzHqm4vpAPxcMuOHE.kJyRBUOytumOAuhP0uEJgMimZvW', 1, '2026-11-05 16:06:59'),
(5, '$2y$10$K/vAEVbJLff4jbha09TD1emHxL0gnvxVhi3O.oKMFTF7TdxW7KcRq', 1, '2026-11-05 16:21:20'),
(6, '$2y$10$dCVSNC.s5/UTpO5tnVQTnesqJsEdNEGgqpO5x3OBYcvFZ5A9Fsl9a', 1, '2026-11-05 16:26:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `manual_usuario`
--

CREATE TABLE `manual_usuario` (
  `id_manual` tinyint(3) UNSIGNED NOT NULL,
  `titulo` varchar(120) NOT NULL DEFAULT 'Manual de usuario',
  `contenido` mediumtext NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `manual_usuario`
--

INSERT INTO `manual_usuario` (`id_manual`, `titulo`, `contenido`, `actualizado_en`) VALUES
(1, 'Manual de usuario BARBER X', 'BARBER X\nMANUAL DE USUARIO\nSistema web de gesti├│n de barber├¡a\nVersi├│n 1.0 | Octubre de 2026\nGu├¡a para clientes y administradores\n\nAPRENDICEZ:\nMaicol stiben tovar correa\nJefferson valderama\nSteveen Perez\n\n1. Introducci├│n\nBARBER X es una aplicaci├│n web para apoyar la gesti├│n b├ísica de una barber├¡a. Permite que los clientes creen una cuenta, inicien sesi├│n y consulten los servicios disponibles. El perfil de administraci├│n permite gestionar los registros de clientes, barberos y recepcionistas.\nEste manual explica c├│mo ingresar al sistema, utilizar sus opciones principales y resolver problemas comunes. La interfaz utiliza un dise├▒o oscuro con detalles dorados y puede adaptarse a pantallas de computador, tableta y celular.\n\n2. Objetivo del manual\nOrientar al usuario para que pueda utilizar BARBER X de forma correcta, desde el registro o inicio de sesi├│n hasta la consulta de servicios y la administraci├│n de personas autorizadas.\n\n3. Requisitos para utilizar el sistema\nUn navegador web actualizado, como Google Chrome o Microsoft Edge.\nPara ejecutar el proyecto localmente: un entorno de servidor como XAMPP con Apache, PHP y MySQL habilitados.\nLa base de datos MySQL llamada barber_x y las tablas que utiliza la aplicaci├│n.\nLos archivos del proyecto ubicados dentro de la carpeta p├║blica de XAMPP, normalmente htdocs.\nConexi├│n correcta entre PHP y MySQL configurada en conexionBD.php.\nImportante: los archivos entregados incluyen el script database.sql, que crea las tablas recepcionista y cuenta_cliente y agrega servicios de ejemplo. El propio script indica que las tablas cliente, barbero y servicio ya deben existir; por tanto, antes de usarlo debe estar instalada la estructura completa de esas tablas.\n\n4. C├│mo iniciar el sistema\nAbra el panel de control de XAMPP.\nInicie los servicios Apache y MySQL.\nCopie la carpeta del proyecto dentro de C:\\\\xampp\\\\htdocs\\\\. Mantenga las rutas relativas de los archivos.\nAbra phpMyAdmin desde http://localhost/phpmyadmin.\nSeleccione o cree la base de datos barber_x e importe el archivo database.sql. Verifique previamente que las tablas cliente, barbero y servicio existan.\nRevise conexionBD.php: el servidor est├í configurado como localhost, la base de datos como barber_x y el usuario como root. La contrase├▒a est├í vac├¡a en el archivo entregado; aj├║stela si su instalaci├│n de MySQL utiliza otra.\nAbra el sistema desde una direcci├│n local como http://localhost/nombre-de-la-carpeta/index.html. Reemplace nombre-de-la-carpeta por el nombre real de la carpeta del proyecto.\nNo abra index.html haciendo doble clic desde el explorador de archivos: las funciones PHP y las solicitudes a la API necesitan ejecutarse desde el servidor local.\n\nInicio de sesi├│n\n\n5.1 Ingresar como cliente\nEn la pantalla de bienvenida, escriba su correo electr├│nico en el campo ÔÇ£Usuario o correo electr├│nicoÔÇØ.\nEscriba la contrase├▒a registrada.\nPulse ÔÇ£Iniciar sesi├│nÔÇØ.\nSi los datos son correctos, se abrir├í el espacio del cliente.\nSi aparece un mensaje de error, compruebe que el correo y la contrase├▒a est├®n escritos correctamente. La cuenta de cliente se autentica con el correo y la contrase├▒a que se definieron durante el registro.\n\n5.2 Crear una cuenta de cliente\nEn la pantalla de acceso, pulse ÔÇ£Reg├¡strate aqu├¡ÔÇØ.\nComplete nombre, apellido, correo electr├│nico, tel├®fono y contrase├▒a.\nLa contrase├▒a debe tener al menos 8 caracteres.\nPulse ÔÇ£Crear cuenta de clienteÔÇØ.\nSi el registro es v├ílido, el sistema crea o vincula el registro del cliente y abre su sesi├│n.\nEl correo debe tener un formato v├ílido y no puede estar asociado a otra cuenta. Si el sistema indica que ya existe una cuenta, intente iniciar sesi├│n con ella o utilice otro correo.\n\n5.3 Ingresar como administrador\nEn la pantalla de acceso, el administrador debe escribir el usuario configurado y su contrase├▒a autorizada. El c├│digo reconoce el identificador ÔÇ£adminÔÇØ, pero la contrase├▒a v├ílida depende del hash configurado en sesion.php. Solicite al responsable del proyecto las credenciales correctas; no se debe asumir una contrase├▒a por defecto.\n\nEspacio del cliente\n\n6.1 Consultar servicios\nDespu├®s de iniciar sesi├│n, el cliente ve un saludo y la secci├│n ÔÇ£Nuestros serviciosÔÇØ. All├¡ se muestran los servicios activos que est├ín guardados en la base de datos, ordenados por nombre. Cada tarjeta presenta el nombre, una descripci├│n, el precio y la duraci├│n.\n\n6.2 Servicios de ejemplo\nLos valores anteriores corresponden a los datos de ejemplo incluidos en database.sql. El precio y la duraci├│n reales pueden cambiar seg├║n los datos vigentes en la base de datos.\nEn los archivos revisados no aparece una opci├│n de reservar citas desde la pantalla del cliente; esta versi├│n permite consultar los servicios, no confirmar una reserva desde esa vista.\n\n7. Panel de administraci├│n\nEl panel de administraci├│n presenta un men├║ lateral con tres secciones: Clientes, Barberos y Recepcionistas. Seleccione una secci├│n para ver sus registros. En la parte superior se muestran contadores y un campo de b├║squeda.\n\n7.1 Consultar y buscar registros\nSeleccione Clientes, Barberos o Recepcionistas en el men├║.\nRevise la lista que aparece en la tabla.\nUse el campo de b├║squeda para filtrar por nombre o correo electr├│nico.\nLos contadores resumen el total y algunos datos de los registros de la secci├│n seleccionada.\n\n7.2 Crear un registro\nPulse ÔÇ£Agregar barberoÔÇØ o ÔÇ£Agregar recepcionistaÔÇØ, seg├║n corresponda.\nComplete nombre, apellido, correo y tel├®fono.\nPara un barbero, indique tambi├®n su especialidad.\nCuando est├® disponible, seleccione el estado Activo o Inactivo.\nPulse ÔÇ£GuardarÔÇØ. Si desea salir sin guardar, pulse ÔÇ£CancelarÔÇØ o cierre el formulario.\n\n7.3 Editar un registro\nBusque a la persona en la tabla.\nPulse el bot├│n con el s├¡mbolo de l├ípiz en su fila.\nModifique los datos necesarios.\nGuarde los cambios y compruebe que la lista se actualice.\n\n7.4 Eliminar un registro\nLocalice el registro que desea retirar.\nPulse el bot├│n de eliminar (s├¡mbolo de papelera o borrado) en la fila correspondiente.\nConfirme la acci├│n si la interfaz solicita confirmaci├│n.\nAdvertencia: eliminar puede ser una acci├│n irreversible y podr├¡a afectar informaci├│n relacionada. Verifique cuidadosamente el registro antes de continuar.\n\n7.5 Estados y validaciones\nLos campos nombre, apellido, tel├®fono y correo son obligatorios en el formulario de administraci├│n.\nEl correo debe ser v├ílido y no debe repetirse dentro de la misma categor├¡a.\nLos barberos requieren una especialidad.\nLos registros de barberos y recepcionistas pueden marcarse como activos o inactivos.\nSolo una sesi├│n con rol de administrador puede crear, consultar, editar o eliminar registros desde el m├│dulo de administraci├│n.\n\n8. Cerrar sesi├│n\nPulse ÔÇ£Cerrar sesi├│nÔÇØ en la parte superior de la pantalla.\nEl sistema termina la sesi├│n actual.\nPara volver a utilizar el sistema, inicie sesi├│n de nuevo.\nCierre sesi├│n cuando termine de utilizar el sistema, especialmente si comparte el computador con otras personas.\n\n9. Soluci├│n de problemas\n\n10. Recomendaciones de uso y seguridad\nNo comparta su contrase├▒a ni utilice credenciales de administrador en cuentas de uso com├║n.\nUse contrase├▒as de al menos 8 caracteres y evite reutilizar contrase├▒as personales.\nMantenga una copia de seguridad de la base de datos antes de modificar tablas o eliminar registros.\nRegistre informaci├│n real, completa y actualizada.\nEn un despliegue p├║blico, cambie las credenciales de ejemplo, configure una contrase├▒a segura para MySQL y utilice HTTPS.\nSi ocurre un error, anote el mensaje que aparece y comun├¡quelo al encargado del sistema.\n\n11. Alcance de la versi├│n documentada\nEste manual se elabor├│ a partir de los archivos entregados: index.html, styles.css, conexionBD.php, database.sql, personas.php, servicios.php y sesion.php. La versi├│n documentada incluye registro e inicio de sesi├│n de clientes, consulta de servicios y administraci├│n de clientes, barberos y recepcionistas.\nLos archivos proporcionados no muestran un flujo funcional para que el cliente reserve una cita ni un m├│dulo de pagos. Aunque el proyecto est├í orientado a la gesti├│n de una barber├¡a y la base de datos hace referencia a citas, estas funciones no se describen como acciones disponibles en la interfaz revisada.\n\n12. Glosario\nAdministrador: Usuario autorizado para gestionar los registros del sistema.\nCliente: Persona que crea una cuenta e ingresa para consultar los servicios.\nBarbero: Persona que presta los servicios de barber├¡a y puede tener una especialidad registrada.\nRecepcionista: Integrante del personal registrado para apoyar la recepci├│n.\nServicio activo: Servicio que est├í habilitado para aparecer en la consulta del cliente.\nBase de datos: Lugar donde se almacenan los registros que utiliza la aplicaci├│n.\nSesi├│n: Estado temporal que permite reconocer al usuario despu├®s de iniciar sesi├│n.', '2026-10-29 15:33:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcionista`
--

CREATE TABLE `recepcionista` (
  `id_recepcionista` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporte_generado`
--

CREATE TABLE `reporte_generado` (
  `id_reporte` bigint(20) UNSIGNED NOT NULL,
  `generado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `generado_por` varchar(120) NOT NULL,
  `total_citas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_clientes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ingresos` decimal(14,2) NOT NULL DEFAULT 0.00,
  `datos_json` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reporte_generado`
--

INSERT INTO `reporte_generado` (`id_reporte`, `generado_en`, `generado_por`, `total_citas`, `total_clientes`, `ingresos`, `datos_json`) VALUES
(1, '2026-10-27 16:36:28', 'Administrador', 23, 15, 25000.00, '{\"citas\":{\"total\":23,\"pendientes\":20,\"confirmadas\":0,\"completadas\":1,\"canceladas\":2},\"ingresos\":25000,\"clientes\":15,\"servicios\":[{\"servicio\":\"Arreglo de barba con navaja\",\"citas\":21,\"completadas\":0,\"ingresos\":0},{\"servicio\":\"Corte clásico\",\"citas\":2,\"completadas\":1,\"ingresos\":25000},{\"servicio\":\"Corte cl├ísico\",\"citas\":0,\"completadas\":0,\"ingresos\":0},{\"servicio\":\"Corte degradado\",\"citas\":0,\"completadas\":0,\"ingresos\":0},{\"servicio\":\"Corte y barba\",\"citas\":0,\"completadas\":0,\"ingresos\":0}]}'),
(2, '2026-10-29 11:24:09', 'Administrador', 23, 15, 25000.00, '{\"citas\":{\"total\":23,\"pendientes\":20,\"confirmadas\":0,\"completadas\":1,\"canceladas\":2},\"ingresos\":25000,\"clientes\":15,\"servicios\":[{\"servicio\":\"Arreglo de barba con navaja\",\"citas\":21,\"completadas\":0,\"ingresos\":0},{\"servicio\":\"Corte clásico\",\"citas\":2,\"completadas\":1,\"ingresos\":25000},{\"servicio\":\"Corte cl├ísico\",\"citas\":0,\"completadas\":0,\"ingresos\":0},{\"servicio\":\"Corte degradado\",\"citas\":0,\"completadas\":0,\"ingresos\":0},{\"servicio\":\"Corte y barba\",\"citas\":0,\"completadas\":0,\"ingresos\":0}]}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_serv` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `duracion` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio`
--

INSERT INTO `servicio` (`id_serv`, `nombre`, `descripcion`, `precio`, `duracion`, `activo`) VALUES
(1, 'Corte clásico (registro anterior)', 'Servicio anterior conservado solo para consultar el historial.', 25000.00, 40, 1),
(2, 'Corte fade (degradado)', 'Degradado bajo, medio o alto integrado con el largo superior; incluye perfilado de patillas y nuca.', 30000.00, 45, 1),
(3, 'Afeitado clásico con navaja', 'Preparación de la piel, espuma y afeitado preciso con navaja para un acabado suave y definido.', 20000.00, 35, 1),
(4, 'Corte + barba', 'Servicio combinado de corte personalizado y perfilado de barba, con acabado limpio en los contornos.', 42000.00, 65, 1),
(5, 'Corte clásico a tijera o máquina', 'Corte tradicional personalizado a tijera o máquina, con laterales, nuca y contornos definidos.', 25000.00, 40, 1),
(6, 'Perfilado y diseño de barba', 'Recorte y diseño de barba según el rostro, con definición de mejillas, cuello y contornos.', 18000.00, 30, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `video_tutorial`
--

CREATE TABLE `video_tutorial` (
  `id_video` tinyint(3) UNSIGNED NOT NULL,
  `titulo` varchar(120) NOT NULL DEFAULT 'Video explicativo',
  `descripcion` varchar(2000) NOT NULL DEFAULT '',
  `archivo` varchar(100) DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `video_tutorial`
--

INSERT INTO `video_tutorial` (`id_video`, `titulo`, `descripcion`, `archivo`, `actualizado_en`) VALUES
(1, 'Video explicativo de BARBER X', 'Conoce las principales opciones de la pagina en este video.', 'tutorial.mp4', '2026-10-27 15:03:08');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `barbero`
--
ALTER TABLE `barbero`
  ADD PRIMARY KEY (`id_barbero`);

--
-- Indices de la tabla `cita`
--
ALTER TABLE `cita`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `fk_cita_cliente` (`id_clte`),
  ADD KEY `fk_cita_barbero` (`id_barbero`);

--
-- Indices de la tabla `cita_servicio`
--
ALTER TABLE `cita_servicio`
  ADD PRIMARY KEY (`id_cs`),
  ADD KEY `fk_cs_cita` (`id_cita`),
  ADD KEY `fk_cs_servicio` (`id_serv`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_clte`);

--
-- Indices de la tabla `cuenta_barbero`
--
ALTER TABLE `cuenta_barbero`
  ADD PRIMARY KEY (`id_barbero`);

--
-- Indices de la tabla `manual_usuario`
--
ALTER TABLE `manual_usuario`
  ADD PRIMARY KEY (`id_manual`);

--
-- Indices de la tabla `recepcionista`
--
ALTER TABLE `recepcionista`
  ADD PRIMARY KEY (`id_recepcionista`),
  ADD UNIQUE KEY `recepcionista_email_unique` (`email`);

--
-- Indices de la tabla `reporte_generado`
--
ALTER TABLE `reporte_generado`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `reporte_generado_fecha_idx` (`generado_en`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_serv`);

--
-- Indices de la tabla `video_tutorial`
--
ALTER TABLE `video_tutorial`
  ADD PRIMARY KEY (`id_video`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `barbero`
--
ALTER TABLE `barbero`
  MODIFY `id_barbero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cita`
--
ALTER TABLE `cita`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `cita_servicio`
--
ALTER TABLE `cita_servicio`
  MODIFY `id_cs` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_clte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `recepcionista`
--
ALTER TABLE `recepcionista`
  MODIFY `id_recepcionista` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reporte_generado`
--
ALTER TABLE `reporte_generado`
  MODIFY `id_reporte` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `servicio`
--
ALTER TABLE `servicio`
  MODIFY `id_serv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cita`
--
ALTER TABLE `cita`
  ADD CONSTRAINT `fk_cita_barbero` FOREIGN KEY (`id_barbero`) REFERENCES `barbero` (`id_barbero`),
  ADD CONSTRAINT `fk_cita_cliente` FOREIGN KEY (`id_clte`) REFERENCES `cliente` (`id_clte`);

--
-- Filtros para la tabla `cita_servicio`
--
ALTER TABLE `cita_servicio`
  ADD CONSTRAINT `fk_cs_cita` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`),
  ADD CONSTRAINT `fk_cs_servicio` FOREIGN KEY (`id_serv`) REFERENCES `servicio` (`id_serv`);

--
-- Filtros para la tabla `cuenta_barbero`
--
ALTER TABLE `cuenta_barbero`
  ADD CONSTRAINT `cuenta_barbero_barbero_fk` FOREIGN KEY (`id_barbero`) REFERENCES `barbero` (`id_barbero`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
