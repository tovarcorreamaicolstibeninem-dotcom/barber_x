<?php
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderCitas(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function leerCita(): array
{
    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responderCitas(400, ["error" => "Los datos enviados no son válidos."]);
    }
    return $datos;
}

$usuarioSesion = $_SESSION["usuario"] ?? null;
if (!is_array($usuarioSesion) || !in_array($usuarioSesion["rol"] ?? "", ["admin", "cliente"], true)) {
    responderCitas(401, ["error" => "Inicia sesión para gestionar las citas."]);
}

try {
    require __DIR__ . "/../conexionBD.php";
    $metodo = $_SERVER["REQUEST_METHOD"];

    if ($metodo === "GET") {
        $sql = "SELECT c.id_cita AS id, c.fecha_hora, LOWER(c.estado) AS estado, c.notas,
                       COALESCE(c.precio, servicios.precio_calculado, 0) AS precio,
                       COALESCE(servicios.nombres, 'Servicio sin detallar') AS servicio,
                       CONCAT(b.nombre, ' ', b.apellido) AS barbero,
                       cl.nombre AS cliente_nombre, cl.apellido AS cliente_apellido,
                       cl.email AS cliente_email, cl.telefono AS cliente_telefono
                FROM cita c
                INNER JOIN cliente cl ON cl.id_clte = c.id_clte
                INNER JOIN barbero b ON b.id_barbero = c.id_barbero
                LEFT JOIN (
                    SELECT cs.id_cita,
                           GROUP_CONCAT(s.nombre ORDER BY s.nombre SEPARATOR ', ') AS nombres,
                           SUM(COALESCE(cs.precio, s.precio) * COALESCE(cs.cantidad, 1)) AS precio_calculado
                    FROM cita_servicio cs
                    INNER JOIN servicio s ON s.id_serv = cs.id_serv
                    GROUP BY cs.id_cita
                ) servicios ON servicios.id_cita = c.id_cita";
        if ($usuarioSesion["rol"] === "cliente") {
            $consulta = $conexion->prepare($sql . " WHERE c.id_clte = :id ORDER BY c.fecha_hora DESC, c.id_cita DESC");
            $consulta->execute(["id" => $usuarioSesion["id"]]);
        } else {
            $consulta = $conexion->query($sql . " ORDER BY c.fecha_hora DESC, c.id_cita DESC");
        }
        responderCitas(200, $consulta->fetchAll());
    }

    if ($metodo === "POST") {
        if ($usuarioSesion["rol"] !== "cliente") {
            responderCitas(403, ["error" => "Solo los clientes pueden agendar citas."]);
        }
        $datos = leerCita();
        $idServicio = filter_var($datos["id_servicio"] ?? null, FILTER_VALIDATE_INT);
        $idBarbero = filter_var($datos["id_barbero"] ?? null, FILTER_VALIDATE_INT);
        $fechaTexto = trim((string)($datos["fecha_hora"] ?? ""));
        $notas = trim((string)($datos["notas"] ?? ""));
        $fecha = DateTime::createFromFormat("!Y-m-d\\TH:i", $fechaTexto);
        $erroresFecha = DateTime::getLastErrors();
        if (!$idServicio || $idServicio < 1 || !$idBarbero || $idBarbero < 1 || !$fecha || ($erroresFecha && ($erroresFecha["warning_count"] || $erroresFecha["error_count"]))) {
            responderCitas(422, ["error" => "Selecciona un servicio, un barbero y una fecha y hora válidas."]);
        }
        if ($fecha <= new DateTime()) {
            responderCitas(422, ["error" => "La cita debe programarse para una fecha futura."]);
        }
        if (function_exists("mb_strlen") ? mb_strlen($notas, "UTF-8") > 500 : strlen($notas) > 500) {
            responderCitas(422, ["error" => "Las notas no pueden superar 500 caracteres."]);
        }

        $servicio = $conexion->prepare("SELECT precio FROM servicio WHERE id_serv = :id AND activo = 1");
        $servicio->execute(["id" => $idServicio]);
        $filaServicio = $servicio->fetch();
        if (!$filaServicio) {
            responderCitas(422, ["error" => "El servicio seleccionado no está disponible."]);
        }
        $barbero = $conexion->prepare("SELECT id_barbero FROM barbero WHERE id_barbero = :id AND activo = 1");
        $barbero->execute(["id" => $idBarbero]);
        if (!$barbero->fetch()) {
            responderCitas(422, ["error" => "El barbero seleccionado no está disponible."]);
        }
        $conexion->beginTransaction();
        $crear = $conexion->prepare(
            "INSERT INTO cita (id_clte, id_barbero, fecha_hora, estado, precio, notas)
             VALUES (:cliente, :barbero, :fecha, 'Pendiente', :precio, :notas)"
        );
        $crear->execute([
            "cliente" => $usuarioSesion["id"],
            "barbero" => $idBarbero,
            "fecha" => $fecha->format("Y-m-d H:i:s"),
            "precio" => $filaServicio["precio"],
            "notas" => $notas === "" ? null : $notas,
        ]);
        $idCita = (int)$conexion->lastInsertId();
        $asociarServicio = $conexion->prepare(
            "INSERT INTO cita_servicio (id_cita, id_serv, cantidad, precio)
             VALUES (:cita, :servicio, 1, :precio)"
        );
        $asociarServicio->execute([
            "cita" => $idCita,
            "servicio" => $idServicio,
            "precio" => $filaServicio["precio"],
        ]);
        $conexion->commit();
        responderCitas(201, ["id" => $idCita, "mensaje" => "Tu cita fue agendada y registrada."]);
    }

    if ($metodo === "PUT") {
        if ($usuarioSesion["rol"] !== "admin") {
            responderCitas(403, ["error" => "Solo el administrador puede actualizar el estado de las citas."]);
        }
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
        $estado = (string)(leerCita()["estado"] ?? "");
        $estadosPermitidos = ["pendiente", "confirmada", "completada", "cancelada"];
        if (!$id || $id < 1 || !in_array($estado, $estadosPermitidos, true)) {
            responderCitas(422, ["error" => "La cita o el estado seleccionado no son válidos."]);
        }
        $estadoDb = ucfirst($estado);
        $actualizar = $conexion->prepare("UPDATE cita SET estado = :estado WHERE id_cita = :id");
        $actualizar->execute(["estado" => $estadoDb, "id" => $id]);
        if ($actualizar->rowCount() === 0) {
            $existe = $conexion->prepare("SELECT id_cita FROM cita WHERE id_cita = :id");
            $existe->execute(["id" => $id]);
            if (!$existe->fetch()) {
                responderCitas(404, ["error" => "No se encontró la cita solicitada."]);
            }
        }
        responderCitas(200, ["mensaje" => "Estado de la cita actualizado."]);
    }

    header("Allow: GET, POST, PUT");
    responderCitas(405, ["error" => "Método no permitido."]);
} catch (PDOException $error) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log("Error en la API de citas de BARBER x: " . $error->getMessage());
    responderCitas(500, ["error" => "No fue posible completar la operación de citas. Verifica la base de datos."]);
}
