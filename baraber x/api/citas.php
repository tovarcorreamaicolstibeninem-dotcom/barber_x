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
if (!is_array($usuarioSesion) || !in_array($usuarioSesion["rol"] ?? "", ["admin", "cliente", "barbero"], true)) {
    responderCitas(401, ["error" => "Inicia sesión para gestionar las citas."]);
}
session_write_close();

try {
    require __DIR__ . "/../conexionBD.php";
    if ($usuarioSesion["rol"] === "barbero") {
        $cuentaAutorizada = $conexion->prepare(
            "SELECT 1
             FROM cuenta_barbero cb
             INNER JOIN barbero b ON b.id_barbero = cb.id_barbero
             WHERE cb.id_barbero = :id AND cb.autorizada_por_admin = 1 AND b.activo = 1"
        );
        $cuentaAutorizada->execute(["id" => $usuarioSesion["id"]]);
        if (!$cuentaAutorizada->fetch()) {
            responderCitas(401, ["error" => "La cuenta de barbero no está autorizada. Contacta al administrador."]);
        }
    }
    $metodo = $_SERVER["REQUEST_METHOD"];

    if ($metodo === "GET" && ($_GET["accion"] ?? "") === "horarios") {
        $idBarbero = filter_var($_GET["id_barbero"] ?? null, FILTER_VALIDATE_INT);
        $fechaTexto = trim((string)($_GET["fecha"] ?? ""));
        $fecha = DateTime::createFromFormat("!Y-m-d", $fechaTexto);
        $erroresFecha = DateTime::getLastErrors();
        if (!$idBarbero || $idBarbero < 1 || !$fecha || $fecha->format("Y-m-d") !== $fechaTexto || ($erroresFecha && ($erroresFecha["warning_count"] || $erroresFecha["error_count"]))) {
            responderCitas(422, ["error" => "Selecciona un barbero y una fecha válidos."]);
        }
        if ($usuarioSesion["rol"] === "barbero" && $idBarbero !== (int)$usuarioSesion["id"]) {
            responderCitas(403, ["error" => "Solo puedes consultar tus propios horarios."]);
        }

        $barbero = $conexion->prepare("SELECT id_barbero FROM barbero WHERE id_barbero = :id AND activo = 1");
        $barbero->execute(["id" => $idBarbero]);
        if (!$barbero->fetch()) {
            responderCitas(422, ["error" => "El barbero seleccionado no está disponible."]);
        }

        $idCitaExcluir = null;
        if (isset($_GET["id_cita"])) {
            $idCitaExcluir = filter_var($_GET["id_cita"], FILTER_VALIDATE_INT);
            if (!$idCitaExcluir || $idCitaExcluir < 1 || $usuarioSesion["rol"] !== "cliente") {
                responderCitas(422, ["error" => "La cita que intentas reprogramar no es válida."]);
            }
            $citaPropia = $conexion->prepare(
                "SELECT id_cita FROM cita
                 WHERE id_cita = :cita AND id_clte = :cliente
                   AND LOWER(estado) IN ('pendiente', 'confirmada') AND fecha_hora > NOW()"
            );
            $citaPropia->execute(["cita" => $idCitaExcluir, "cliente" => $usuarioSesion["id"]]);
            if (!$citaPropia->fetch()) {
                responderCitas(404, ["error" => "La cita no está disponible para reprogramar."]);
            }
        }

        $sqlOcupadas = "SELECT DATE_FORMAT(fecha_hora, '%H:%i') AS hora
             FROM cita
             WHERE id_barbero = :barbero
               AND fecha_hora >= :inicio
               AND fecha_hora < :fin
               AND LOWER(estado) <> 'cancelada'";
        if ($idCitaExcluir !== null) {
            $sqlOcupadas .= " AND id_cita <> :cita";
        }
        $ocupadas = $conexion->prepare(
            $sqlOcupadas
        );
        $fechaFin = (clone $fecha)->modify("+1 day");
        $parametrosOcupadas = [
            "barbero" => $idBarbero,
            "inicio" => $fecha->format("Y-m-d 00:00:00"),
            "fin" => $fechaFin->format("Y-m-d 00:00:00"),
        ];
        if ($idCitaExcluir !== null) {
            $parametrosOcupadas["cita"] = $idCitaExcluir;
        }
        $ocupadas->execute($parametrosOcupadas);
        $minutosOcupados = [];
        foreach (array_column($ocupadas->fetchAll(), "hora") as $horaOcupada) {
            $partesHora = explode(":", $horaOcupada);
            $minutosOcupados[] = ((int)$partesHora[0] * 60) + (int)$partesHora[1];
        }
        $ahora = new DateTime();
        $horarios = [];
        for ($minuto = 7 * 60; $minuto + 45 <= 20 * 60; $minuto += 45) {
            $hora = sprintf("%02d:%02d", intdiv($minuto, 60), $minuto % 60);
            $horario = DateTime::createFromFormat("!Y-m-d H:i", "$fechaTexto $hora");
            $agendada = false;
            foreach ($minutosOcupados as $minutoOcupado) {
                if ($minuto < $minutoOcupado + 45 && $minuto + 45 > $minutoOcupado) {
                    $agendada = true;
                    break;
                }
            }
            $pasada = $horario <= $ahora;
            $horarios[] = [
                "hora" => $hora,
                "disponible" => !$agendada && !$pasada,
                "estado" => $agendada || $pasada ? "No disponible" : "Disponible",
            ];
        }
        responderCitas(200, $horarios);
    }

    if ($metodo === "GET") {
        $sql = "SELECT c.id_cita AS id, c.id_barbero, c.fecha_hora, LOWER(c.estado) AS estado, c.notas,
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
        } elseif ($usuarioSesion["rol"] === "barbero") {
            $consulta = $conexion->prepare($sql . " WHERE c.id_barbero = :id ORDER BY c.fecha_hora DESC, c.id_cita DESC");
            $consulta->execute(["id" => $usuarioSesion["id"]]);
        } else {
            $consulta = $conexion->query($sql . " ORDER BY c.fecha_hora DESC, c.id_cita DESC");
        }
        $citas = $consulta->fetchAll();
        if ($usuarioSesion["rol"] === "barbero") {
            foreach ($citas as &$cita) {
                unset($cita["cliente_email"], $cita["cliente_telefono"], $cita["notas"]);
            }
            unset($cita);
        }
        responderCitas(200, $citas);
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
        $minutoDelDia = ((int)$fecha->format("H")) * 60 + (int)$fecha->format("i");
        if ($minutoDelDia < 7 * 60 || $minutoDelDia + 45 > 20 * 60 || ($minutoDelDia - 7 * 60) % 45 !== 0) {
            responderCitas(422, ["error" => "Selecciona un horario entre las 7:00 AM y las 8:00 PM, en intervalos de 45 minutos."]);
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
        $bloquearBarbero = $conexion->prepare(
            "SELECT id_barbero FROM barbero WHERE id_barbero = :barbero AND activo = 1 FOR UPDATE"
        );
        $bloquearBarbero->execute(["barbero" => $idBarbero]);
        if (!$bloquearBarbero->fetch()) {
            $conexion->rollBack();
            responderCitas(422, ["error" => "El barbero seleccionado ya no está disponible."]);
        }
        $ocupada = $conexion->prepare(
            "SELECT id_cita FROM cita
             WHERE id_barbero = :barbero
               AND fecha_hora < :fin
               AND DATE_ADD(fecha_hora, INTERVAL 45 MINUTE) > :inicio
               AND LOWER(estado) <> 'cancelada'
             FOR UPDATE"
        );
        $fechaFin = (clone $fecha)->modify("+45 minutes");
        $ocupada->execute([
            "barbero" => $idBarbero,
            "inicio" => $fecha->format("Y-m-d H:i:s"),
            "fin" => $fechaFin->format("Y-m-d H:i:s"),
        ]);
        if ($ocupada->fetch()) {
            $conexion->rollBack();
            responderCitas(409, ["error" => "Ese horario ya está agendado. Selecciona otra hora disponible."]);
        }
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
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
        $datos = leerCita();
        if (!$id || $id < 1) {
            responderCitas(422, ["error" => "La cita o el estado seleccionado no son válidos."]);
        }

        if ($usuarioSesion["rol"] === "cliente") {
            $accion = (string)($datos["accion"] ?? "");
            if (!in_array($accion, ["cancelar", "reprogramar"], true)) {
                responderCitas(422, ["error" => "La acción solicitada para la cita no es válida."]);
            }

            if ($accion === "cancelar") {
                $conexion->beginTransaction();
                $cita = $conexion->prepare(
                    "SELECT id_cita, fecha_hora, LOWER(estado) AS estado
                     FROM cita
                     WHERE id_cita = :cita AND id_clte = :cliente
                     FOR UPDATE"
                );
                $cita->execute(["cita" => $id, "cliente" => $usuarioSesion["id"]]);
                $filaCita = $cita->fetch();
                if (!$filaCita) {
                    $conexion->rollBack();
                    responderCitas(404, ["error" => "No se encontró una cita activa de tu cuenta con ese número."]);
                }
                if (!in_array($filaCita["estado"], ["pendiente", "confirmada"], true) || new DateTime($filaCita["fecha_hora"]) <= new DateTime()) {
                    $conexion->rollBack();
                    responderCitas(422, ["error" => "Solo puedes cancelar citas futuras que aún estén pendientes."]);
                }
                $actualizar = $conexion->prepare(
                    "UPDATE cita SET estado = 'Cancelada'
                     WHERE id_cita = :cita AND id_clte = :cliente"
                );
                $actualizar->execute(["cita" => $id, "cliente" => $usuarioSesion["id"]]);
                $conexion->commit();
                responderCitas(200, ["mensaje" => "La cita fue cancelada."]);
            }

            $idBarbero = filter_var($datos["id_barbero"] ?? null, FILTER_VALIDATE_INT);
            $fechaTexto = trim((string)($datos["fecha_hora"] ?? ""));
            $fecha = DateTime::createFromFormat("!Y-m-d\\TH:i", $fechaTexto);
            $erroresFecha = DateTime::getLastErrors();
            if (!$idBarbero || $idBarbero < 1 || !$fecha || ($erroresFecha && ($erroresFecha["warning_count"] || $erroresFecha["error_count"]))) {
                responderCitas(422, ["error" => "Selecciona un barbero y una fecha y hora válidas para reprogramar."]);
            }
            $minutoDelDia = ((int)$fecha->format("H")) * 60 + (int)$fecha->format("i");
            if ($minutoDelDia < 7 * 60 || $minutoDelDia + 45 > 20 * 60 || ($minutoDelDia - 7 * 60) % 45 !== 0 || $fecha <= new DateTime()) {
                responderCitas(422, ["error" => "Selecciona una hora futura entre las 7:00 AM y las 8:00 PM, en intervalos de 45 minutos."]);
            }

            $conexion->beginTransaction();
            $bloquearBarbero = $conexion->prepare(
                "SELECT id_barbero FROM barbero WHERE id_barbero = :barbero AND activo = 1 FOR UPDATE"
            );
            $bloquearBarbero->execute(["barbero" => $idBarbero]);
            if (!$bloquearBarbero->fetch()) {
                $conexion->rollBack();
                responderCitas(422, ["error" => "El barbero seleccionado ya no está disponible."]);
            }
            $cita = $conexion->prepare(
                "SELECT id_cita, fecha_hora, LOWER(estado) AS estado
                 FROM cita
                 WHERE id_cita = :cita AND id_clte = :cliente
                 FOR UPDATE"
            );
            $cita->execute(["cita" => $id, "cliente" => $usuarioSesion["id"]]);
            $filaCita = $cita->fetch();
            if (!$filaCita) {
                $conexion->rollBack();
                responderCitas(404, ["error" => "No se encontró una cita activa de tu cuenta con ese número."]);
            }
            if (!in_array($filaCita["estado"], ["pendiente", "confirmada"], true) || new DateTime($filaCita["fecha_hora"]) <= new DateTime()) {
                $conexion->rollBack();
                responderCitas(422, ["error" => "Solo puedes reprogramar citas futuras que aún estén pendientes."]);
            }

            $fechaFin = (clone $fecha)->modify("+45 minutes");
            $ocupada = $conexion->prepare(
                "SELECT id_cita FROM cita
                 WHERE id_barbero = :barbero
                   AND fecha_hora < :fin
                   AND DATE_ADD(fecha_hora, INTERVAL 45 MINUTE) > :inicio
                   AND LOWER(estado) <> 'cancelada'
                   AND id_cita <> :cita
                 FOR UPDATE"
            );
            $ocupada->execute([
                "barbero" => $idBarbero,
                "inicio" => $fecha->format("Y-m-d H:i:s"),
                "fin" => $fechaFin->format("Y-m-d H:i:s"),
                "cita" => $id,
            ]);
            if ($ocupada->fetch()) {
                $conexion->rollBack();
                responderCitas(409, ["error" => "Ese horario ya está agendado. Selecciona otra hora disponible."]);
            }

            $actualizar = $conexion->prepare(
                "UPDATE cita SET id_barbero = :barbero, fecha_hora = :fecha, estado = 'Pendiente'
                 WHERE id_cita = :cita AND id_clte = :cliente"
            );
            $actualizar->execute([
                "barbero" => $idBarbero,
                "fecha" => $fecha->format("Y-m-d H:i:s"),
                "cita" => $id,
                "cliente" => $usuarioSesion["id"],
            ]);
            $conexion->commit();
            responderCitas(200, ["mensaje" => "La cita fue reprogramada."]);
        }

        if ($usuarioSesion["rol"] !== "admin") {
            responderCitas(403, ["error" => "No tienes permiso para actualizar esta cita."]);
        }
        $estado = (string)($datos["estado"] ?? "");
        $estadosPermitidos = ["pendiente", "completada", "cancelada"];
        if (!in_array($estado, $estadosPermitidos, true)) {
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
