<?php
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderServicios(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$usuarioSesion = $_SESSION["usuario"] ?? null;
if (!is_array($usuarioSesion) || !in_array($usuarioSesion["rol"] ?? "", ["admin", "cliente"], true)) {
    responderServicios(401, ["error" => "Inicia sesión para consultar los servicios."]);
}

function leerServicio(): array
{
    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responderServicios(400, ["error" => "Los datos enviados no son válidos."]);
    }
    return $datos;
}

function validarServicio(array $datos): array
{
    $nombre = trim((string)($datos["nombre"] ?? ""));
    $descripcion = trim((string)($datos["descripcion"] ?? ""));
    $precio = trim((string)($datos["precio"] ?? ""));
    $duracion = filter_var($datos["duracion"] ?? null, FILTER_VALIDATE_INT);
    $activo = (string)($datos["activo"] ?? "1");
    $longitudNombre = function_exists("mb_strlen") ? mb_strlen($nombre, "UTF-8") : strlen($nombre);
    $longitudDescripcion = function_exists("mb_strlen") ? mb_strlen($descripcion, "UTF-8") : strlen($descripcion);

    if ($nombre === "" || $longitudNombre > 100) {
        responderServicios(422, ["error" => "El nombre del servicio es obligatorio y no puede superar 100 caracteres."]);
    }
    if ($longitudDescripcion > 2000) {
        responderServicios(422, ["error" => "La descripción no puede superar 2000 caracteres."]);
    }
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $precio)) {
        responderServicios(422, ["error" => "Ingresa un precio válido, máximo 99.999.999,99."]);
    }
    if (!$duracion || $duracion < 5 || $duracion > 720) {
        responderServicios(422, ["error" => "La duración debe estar entre 5 y 720 minutos."]);
    }
    if (!in_array($activo, ["0", "1"], true)) {
        responderServicios(422, ["error" => "El estado del servicio no es válido."]);
    }
    $partesPrecio = explode(".", $precio, 2);
    $precioEntero = ltrim($partesPrecio[0], "0");
    return [
        "nombre" => $nombre,
        "descripcion" => $descripcion,
        "precio" => ($precioEntero === "" ? "0" : $precioEntero) . "." . str_pad($partesPrecio[1] ?? "", 2, "0"),
        "duracion" => $duracion,
        "activo" => (int)$activo,
    ];
}

try {
    require __DIR__ . "/../conexionBD.php";
    $metodo = $_SERVER["REQUEST_METHOD"];
    $esAdmin = $usuarioSesion["rol"] === "admin";

    if ($metodo === "GET") {
        $sql = "SELECT id_serv AS id, nombre, descripcion, precio, duracion, activo FROM servicio";
        if (!$esAdmin) {
            $sql .= " WHERE activo = 1";
        }
        $consulta = $conexion->query($sql . " ORDER BY activo DESC, nombre");
        responderServicios(200, $consulta->fetchAll());
    }
    if (!$esAdmin) {
        responderServicios(403, ["error" => "Solo el administrador puede gestionar los servicios."]);
    }
    if (!in_array($metodo, ["POST", "PUT"], true)) {
        header("Allow: GET, POST, PUT");
        responderServicios(405, ["error" => "Método no permitido."]);
    }

    $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
    if ($metodo === "PUT" && (!$id || $id < 1)) {
        responderServicios(400, ["error" => "El identificador del servicio no es válido."]);
    }
    $servicio = validarServicio(leerServicio());
    $sqlDuplicado = "SELECT id_serv FROM servicio WHERE nombre = :nombre";
    if ($id) {
        $sqlDuplicado .= " AND id_serv <> :id";
    }
    $duplicado = $conexion->prepare($sqlDuplicado . " LIMIT 1");
    $parametrosDuplicado = ["nombre" => $servicio["nombre"]];
    if ($id) {
        $parametrosDuplicado["id"] = $id;
    }
    $duplicado->execute($parametrosDuplicado);
    if ($duplicado->fetch()) {
        responderServicios(409, ["error" => "Ya existe un servicio con ese nombre."]);
    }

    if ($metodo === "POST") {
        $guardar = $conexion->prepare(
            "INSERT INTO servicio (nombre, descripcion, precio, duracion, activo)
             VALUES (:nombre, :descripcion, :precio, :duracion, :activo)"
        );
        $guardar->execute($servicio);
        $servicio["id"] = (int)$conexion->lastInsertId();
        responderServicios(201, $servicio);
    }

    $servicio["id"] = (int)$id;
    $guardar = $conexion->prepare(
        "UPDATE servicio
         SET nombre = :nombre, descripcion = :descripcion, precio = :precio,
             duracion = :duracion, activo = :activo
         WHERE id_serv = :id"
    );
    $guardar->execute($servicio);
    if ($guardar->rowCount() === 0) {
        $existe = $conexion->prepare("SELECT id_serv FROM servicio WHERE id_serv = :id");
        $existe->execute(["id" => $id]);
        if (!$existe->fetch()) {
            responderServicios(404, ["error" => "No se encontró el servicio solicitado."]);
        }
    }
    responderServicios(200, $servicio);
} catch (PDOException $error) {
    if ($error->getCode() === "23000") {
        responderServicios(409, ["error" => "Ya existe un servicio con ese nombre."]);
    }
    error_log("Error al consultar servicios de BARBER x: " . $error->getMessage());
    responderServicios(500, ["error" => "No fue posible guardar o cargar los servicios. Verifica la base de datos."]);
}
