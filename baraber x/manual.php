<?php
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderManual(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION["usuario"])) {
    responderManual(401, ["error" => "Inicia sesión para consultar el manual."]);
}

try {
    require __DIR__ . "/../conexionBD.php";
    $metodo = $_SERVER["REQUEST_METHOD"];
    if ($metodo === "GET") {
        $manual = $conexion->query("SELECT titulo, contenido, actualizado_en FROM manual_usuario WHERE id_manual = 1")->fetch();
        responderManual(200, $manual ?: ["titulo" => "Manual de usuario", "contenido" => "", "actualizado_en" => null]);
    }

    if ($metodo === "PUT") {
        if ($_SESSION["usuario"]["rol"] !== "admin") {
            responderManual(403, ["error" => "Solo el administrador puede editar el manual."]);
        }
        $datos = json_decode(file_get_contents("php://input"), true);
        if (!is_array($datos)) {
            responderManual(400, ["error" => "Los datos enviados no son válidos."]);
        }
        $titulo = trim((string)($datos["titulo"] ?? ""));
        $contenido = trim((string)($datos["contenido"] ?? ""));
        $longitudTitulo = function_exists("mb_strlen") ? mb_strlen($titulo, "UTF-8") : strlen($titulo);
        $longitudContenido = function_exists("mb_strlen") ? mb_strlen($contenido, "UTF-8") : strlen($contenido);
        if ($titulo === "" || $longitudTitulo > 120 || $contenido === "" || $longitudContenido > 100000) {
            responderManual(422, ["error" => "Escribe un título y contenido; el título admite 120 caracteres y el manual 100.000."]);
        }
        $guardar = $conexion->prepare(
            "INSERT INTO manual_usuario (id_manual, titulo, contenido) VALUES (1, :titulo, :contenido)
             ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), contenido = VALUES(contenido)"
        );
        $guardar->execute(["titulo" => $titulo, "contenido" => $contenido]);
        responderManual(200, ["mensaje" => "El manual fue actualizado."]);
    }

    if ($metodo === "DELETE") {
        if ($_SESSION["usuario"]["rol"] !== "admin") {
            responderManual(403, ["error" => "Solo el administrador puede eliminar el manual."]);
        }
        $conexion->exec("DELETE FROM manual_usuario WHERE id_manual = 1");
        responderManual(200, ["mensaje" => "El manual fue eliminado."]);
    }

    header("Allow: GET, PUT, DELETE");
    responderManual(405, ["error" => "Método no permitido."]);
} catch (PDOException $error) {
    error_log("Error en el manual de BARBER x: " . $error->getMessage());
    responderManual(500, ["error" => "No fue posible gestionar el manual. Verifica la base de datos."]);
}
