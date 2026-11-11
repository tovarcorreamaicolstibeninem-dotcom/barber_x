<?php
// Carga utilidades para el registro de errores y prepara una sesión segura.
require_once __DIR__ . "/calidad_logger.php";
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

// Devuelve JSON con el estado HTTP solicitado y finaliza el procesamiento.
function responderCalidad(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Lee y valida que el cuerpo de la solicitud contenga un objeto JSON.
function leerDatosCalidad(): array
{
    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responderCalidad(400, ["error" => "Los datos enviados no son válidos."]);
    }
    return $datos;
}

// Crea el esquema de seguimiento de errores si la instalación todavía no lo tiene.
function asegurarTablaRegistroCalidad(PDO $conexion): void
{
    $conexion->exec(
        "CREATE TABLE IF NOT EXISTS registro_calidad (
            id_registro BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo ENUM('error', 'sugerencia') NOT NULL,
            origen ENUM('aplicacion', 'cliente', 'administrador') NOT NULL,
            titulo VARCHAR(120) NOT NULL,
            detalle TEXT NOT NULL,
            severidad ENUM('baja', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
            estado ENUM('nuevo', 'en_revision', 'en_proceso', 'resuelto') NOT NULL DEFAULT 'nuevo',
            id_cliente INT DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id_registro),
            KEY registro_calidad_estado_fecha_idx (estado, creado_en),
            KEY registro_calidad_tipo_fecha_idx (tipo, creado_en),
            CONSTRAINT registro_calidad_cliente_fk FOREIGN KEY (id_cliente)
                REFERENCES cliente (id_clte) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

$usuario = $_SESSION["usuario"] ?? null;
// Permite el envío al cliente y reserva la consulta/gestión de registros al administrador.
if (!is_array($usuario) || !in_array($usuario["rol"] ?? "", ["admin", "cliente"], true)) {
    responderCalidad(401, ["error" => "Inicia sesión con una cuenta autorizada para enviar sugerencias o consultar calidad."]);
}
session_write_close();

try {
    require __DIR__ . "/../conexionBD.php";
    asegurarTablaRegistroCalidad($conexion);
    $metodo = $_SERVER["REQUEST_METHOD"];

    // El administrador consulta los registros, métricas agregadas y sus estados actuales.
    if ($metodo === "GET") {
        if ($usuario["rol"] !== "admin") {
            responderCalidad(403, ["error" => "Solo el administrador puede consultar los registros de calidad."]);
        }

        $consulta = $conexion->query(
            "SELECT r.id_registro AS id, r.tipo, r.origen, r.titulo, r.detalle, r.severidad, r.estado,
                    r.creado_en, r.actualizado_en, r.id_cliente,
                    CASE WHEN r.id_cliente IS NULL THEN NULL
                         ELSE CONCAT(c.nombre, ' ', c.apellido) END AS cliente
             FROM registro_calidad r
             LEFT JOIN cliente c ON c.id_clte = r.id_cliente
             ORDER BY r.creado_en DESC, r.id_registro DESC
             LIMIT 200"
        );
        $registros = $consulta->fetchAll();
        $resumen = $conexion->query(
            "SELECT COUNT(*) AS total,
                    SUM(tipo = 'error' AND estado <> 'resuelto') AS errores_abiertos,
                    SUM(tipo = 'sugerencia' AND estado <> 'resuelto') AS sugerencias_pendientes,
                    SUM(estado = 'resuelto') AS resueltos
             FROM registro_calidad"
        )->fetch();
        foreach (["total", "errores_abiertos", "sugerencias_pendientes", "resueltos"] as $campo) {
            $resumen[$campo] = (int)($resumen[$campo] ?? 0);
        }

        responderCalidad(200, [
            "registros" => $registros,
            "resumen" => $resumen,
        ]);
    }

    // Guarda sugerencias o errores de clientes y registros creados por administración.
    if ($metodo === "POST") {
        $datos = leerDatosCalidad();
        $titulo = trim((string)($datos["titulo"] ?? ""));
        $detalle = trim((string)($datos["detalle"] ?? ""));
        $longitudTitulo = function_exists("mb_strlen") ? mb_strlen($titulo, "UTF-8") : strlen($titulo);
        $longitudDetalle = function_exists("mb_strlen") ? mb_strlen($detalle, "UTF-8") : strlen($detalle);
        if ($titulo === "" || $longitudTitulo > 120 || $detalle === "" || $longitudDetalle > 2000) {
            responderCalidad(422, ["error" => "Escribe un título y un detalle; el título admite 120 caracteres y el detalle 2.000."]);
        }

        if ($usuario["rol"] === "cliente") {
            $tipo = (string)($datos["tipo"] ?? "sugerencia");
            if (!in_array($tipo, ["error", "sugerencia"], true)) {
                responderCalidad(422, ["error" => "Selecciona si deseas reportar un error o enviar una sugerencia."]);
            }
            $origen = "cliente";
            $severidad = "media";
            $idCliente = (int)$usuario["id"];
        } else {
            $tipo = (string)($datos["tipo"] ?? "");
            $severidad = (string)($datos["severidad"] ?? "media");
            if (!in_array($tipo, ["error", "sugerencia"], true)
                || !in_array($severidad, ["baja", "media", "alta", "critica"], true)) {
                responderCalidad(422, ["error" => "Selecciona un tipo y una severidad válidos."]);
            }
            $origen = "administrador";
            $idCliente = null;
        }

        $guardar = $conexion->prepare(
            "INSERT INTO registro_calidad (tipo, origen, titulo, detalle, severidad, id_cliente)
             VALUES (:tipo, :origen, :titulo, :detalle, :severidad, :id_cliente)"
        );
        $guardar->execute([
            "tipo" => $tipo,
            "origen" => $origen,
            "titulo" => $titulo,
            "detalle" => $detalle,
            "severidad" => $severidad,
            "id_cliente" => $idCliente,
        ]);
        responderCalidad(201, [
            "id" => (int)$conexion->lastInsertId(),
            "mensaje" => $tipo === "sugerencia" ? "Tu sugerencia fue enviada para revisión." : "El registro de calidad fue creado.",
        ]);
    }

    // Limita al administrador el cambio de estado de un registro existente.
    if ($metodo === "PUT") {
        if ($usuario["rol"] !== "admin") {
            responderCalidad(403, ["error" => "Solo el administrador puede actualizar el estado de los registros."]);
        }
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
        $datos = leerDatosCalidad();
        $estado = (string)($datos["estado"] ?? "");
        if (!$id || $id < 1 || !in_array($estado, ["nuevo", "en_revision", "en_proceso", "resuelto"], true)) {
            responderCalidad(422, ["error" => "El registro o el estado seleccionado no son válidos."]);
        }
        $actualizar = $conexion->prepare("UPDATE registro_calidad SET estado = :estado WHERE id_registro = :id");
        $actualizar->execute(["estado" => $estado, "id" => $id]);
        if ($actualizar->rowCount() === 0) {
            $existe = $conexion->prepare("SELECT id_registro FROM registro_calidad WHERE id_registro = :id");
            $existe->execute(["id" => $id]);
            if (!$existe->fetch()) {
                responderCalidad(404, ["error" => "No se encontró el registro de calidad solicitado."]);
            }
        }
        responderCalidad(200, ["mensaje" => "El estado del registro fue actualizado."]);
    }

    header("Allow: GET, POST, PUT");
    responderCalidad(405, ["error" => "Método no permitido."]);
} catch (PDOException $error) {
    // Registra el detalle técnico en el servidor y devuelve un mensaje controlado al navegador.
    error_log("Error en el módulo de calidad de BARBER X: " . $error->getMessage());
    responderCalidad(500, ["error" => "No fue posible guardar o consultar el registro. Verifica que la base de datos tenga la tabla de calidad y los permisos necesarios."]);
}
