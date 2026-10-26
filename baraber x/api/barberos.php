<?php
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderBarberos(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION["usuario"])) {
    responderBarberos(401, ["error" => "Inicia sesión para consultar los barberos."]);
}
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET");
    responderBarberos(405, ["error" => "Método no permitido."]);
}

try {
    require __DIR__ . "/../conexionBD.php";
    $barberos = $conexion->query(
        "SELECT id_barbero AS id, nombre, apellido, especialidad
         FROM barbero
         WHERE activo = 1
         ORDER BY nombre, apellido"
    );
    responderBarberos(200, $barberos->fetchAll());
} catch (PDOException $error) {
    error_log("Error al consultar barberos disponibles en BARBER x: " . $error->getMessage());
    responderBarberos(500, ["error" => "No fue posible cargar los barberos disponibles."]);
}
