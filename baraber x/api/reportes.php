<?php
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderReportes(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["rol"] !== "admin") {
    responderReportes(403, ["error" => "Debes iniciar sesión como administrador para consultar los reportes."]);
}
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET");
    responderReportes(405, ["error" => "Método no permitido."]);
}

try {
    require __DIR__ . "/../conexionBD.php";
    $resumen = $conexion->query(
        "SELECT COUNT(*) AS total,
                SUM(LOWER(c.estado) = 'pendiente') AS pendientes,
                SUM(LOWER(c.estado) = 'confirmada') AS confirmadas,
                SUM(LOWER(c.estado) = 'completada') AS completadas,
                SUM(LOWER(c.estado) = 'cancelada') AS canceladas,
                COALESCE(SUM(
                    CASE WHEN LOWER(c.estado) = 'completada'
                    THEN COALESCE(c.precio, servicios.precio_calculado, 0) ELSE 0 END
                ), 0) AS ingresos
         FROM cita c
         LEFT JOIN (
             SELECT cs.id_cita,
                    SUM(COALESCE(cs.precio, s.precio) * COALESCE(cs.cantidad, 1)) AS precio_calculado
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id_serv = cs.id_serv
             GROUP BY cs.id_cita
         ) servicios ON servicios.id_cita = c.id_cita"
    )->fetch();
    $clientes = $conexion->query("SELECT COUNT(*) AS total FROM cliente")->fetch();
    $porServicio = $conexion->query(
        "SELECT s.nombre AS servicio, COUNT(DISTINCT c.id_cita) AS citas,
                COUNT(DISTINCT CASE WHEN LOWER(c.estado) = 'completada' THEN c.id_cita END) AS completadas,
                COALESCE(SUM(
                    CASE WHEN LOWER(c.estado) = 'completada'
                    THEN COALESCE(cs.precio, s.precio) * COALESCE(cs.cantidad, 1) ELSE 0 END
                ), 0) AS ingresos
         FROM servicio s
         LEFT JOIN cita_servicio cs ON cs.id_serv = s.id_serv
         LEFT JOIN cita c ON c.id_cita = cs.id_cita
         GROUP BY s.id_serv, s.nombre
         ORDER BY citas DESC, s.nombre"
    )->fetchAll();
    responderReportes(200, [
        "citas" => array_map("intval", [
            "total" => $resumen["total"],
            "pendientes" => $resumen["pendientes"] ?? 0,
            "confirmadas" => $resumen["confirmadas"] ?? 0,
            "completadas" => $resumen["completadas"] ?? 0,
            "canceladas" => $resumen["canceladas"] ?? 0,
        ]),
        "ingresos" => (float)$resumen["ingresos"],
        "clientes" => (int)$clientes["total"],
        "servicios" => $porServicio,
    ]);
} catch (PDOException $error) {
    error_log("Error al generar reportes de BARBER x: " . $error->getMessage());
    responderReportes(500, ["error" => "No fue posible generar los reportes. Verifica la base de datos."]);
}
