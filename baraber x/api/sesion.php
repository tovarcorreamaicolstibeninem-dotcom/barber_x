<?php
header("Content-Type: application/json; charset=utf-8");

session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderSesion(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function leerDatosSesion(): array
{
    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responderSesion(400, ["error" => "Los datos enviados no son válidos."]);
    }
    return $datos;
}

function iniciarSesion(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION["usuario"] = [
        "rol" => $usuario["rol"],
        "nombre" => $usuario["nombre"],
        "apellido" => $usuario["apellido"],
        "id" => (int)$usuario["id"],
    ];
    responderSesion(200, ["usuario" => $_SESSION["usuario"]]);
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    responderSesion(200, ["usuario" => $_SESSION["usuario"] ?? null]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: GET, POST");
    responderSesion(405, ["error" => "Método no permitido."]);
}

$datos = leerDatosSesion();
$accion = (string)($datos["accion"] ?? "");

if ($accion === "logout") {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $cookie["path"],
            "domain" => $cookie["domain"],
            "secure" => $cookie["secure"],
            "httponly" => $cookie["httponly"],
            "samesite" => $cookie["samesite"],
        ]);
    }
    session_destroy();
    responderSesion(200, ["mensaje" => "Sesión cerrada."]);
}

if ($accion === "login") {
    $identificador = trim((string)($datos["identificador"] ?? ""));
    $contrasena = (string)($datos["contrasena"] ?? "");
    if ($identificador === "" || $contrasena === "") {
        responderSesion(422, ["error" => "Escribe tu usuario o correo y tu contraseña."]);
    }

    $hashAdministrador = '$2y$10$zzN04APUM5/nEU3vlytp3OZNc4JjYdDiaFnzTUJY0JAtuHETWskwq';
    if ($identificador === "admin" && password_verify($contrasena, $hashAdministrador)) {
        iniciarSesion([
            "id" => 0,
            "rol" => "admin",
            "nombre" => "Administrador",
            "apellido" => "",
        ]);
    }

    try {
        require __DIR__ . "/../conexionBD.php";
        $consulta = $conexion->prepare(
            "SELECT id_clte AS id, nombre, apellido, password_hash
             FROM cliente
             WHERE email = :email AND password_hash IS NOT NULL
             LIMIT 1"
        );
        $consulta->execute(["email" => $identificador]);
        $cliente = $consulta->fetch();
        if (!$cliente || empty($cliente["password_hash"]) || !password_verify($contrasena, $cliente["password_hash"])) {
            responderSesion(401, ["error" => "Usuario/correo o contraseña incorrectos."]);
        }
        iniciarSesion([
            "id" => $cliente["id"],
            "rol" => "cliente",
            "nombre" => $cliente["nombre"],
            "apellido" => $cliente["apellido"],
        ]);
    } catch (PDOException $error) {
        error_log("Error al iniciar sesión en BARBER x: " . $error->getMessage());
        responderSesion(500, ["error" => "No fue posible iniciar sesión. Verifica la base de datos."]);
    }
}

if ($accion === "registro") {
    $nombre = trim((string)($datos["nombre"] ?? ""));
    $apellido = trim((string)($datos["apellido"] ?? ""));
    $telefono = trim((string)($datos["telefono"] ?? ""));
    $email = trim((string)($datos["email"] ?? ""));
    $contrasena = (string)($datos["contrasena"] ?? "");

    if ($nombre === "" || $apellido === "" || $telefono === "" || $email === "" || $contrasena === "") {
        responderSesion(422, ["error" => "Completa todos los campos para crear tu cuenta."]);
    }
    $longitud = static fn(string $valor): int => function_exists("mb_strlen") ? mb_strlen($valor, "UTF-8") : strlen($valor);
    if ($longitud($nombre) > 50 || $longitud($apellido) > 50 || $longitud($telefono) > 20 || $longitud($email) > 100) {
        responderSesion(422, ["error" => "Revisa la longitud de los datos ingresados."]);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responderSesion(422, ["error" => "Escribe un correo electrónico válido."]);
    }
    if (strlen($contrasena) < 8) {
        responderSesion(422, ["error" => "La contraseña debe tener al menos 8 caracteres."]);
    }

    try {
        require __DIR__ . "/../conexionBD.php";
        $conexion->beginTransaction();

        $cuentaExistente = $conexion->prepare(
            "SELECT id_clte, password_hash FROM cliente
             WHERE email = :email
             ORDER BY (password_hash IS NOT NULL) DESC, id_clte
             LIMIT 1"
        );
        $cuentaExistente->execute(["email" => $email]);
        $cliente = $cuentaExistente->fetch();
        if ($cliente) {
            if (!empty($cliente["password_hash"])) {
                $conexion->rollBack();
                responderSesion(409, ["error" => "Ya existe una cuenta con ese correo. Inicia sesión o usa otro correo."]);
            }
            $idCliente = (int)$cliente["id_clte"];
            $actualizarCliente = $conexion->prepare(
                "UPDATE cliente SET nombre = :nombre, apellido = :apellido, telefono = :telefono,
                    password_hash = :password_hash
                 WHERE id_clte = :id"
            );
            $actualizarCliente->execute([
                "nombre" => $nombre,
                "apellido" => $apellido,
                "telefono" => $telefono,
                "password_hash" => password_hash($contrasena, PASSWORD_DEFAULT),
                "id" => $idCliente,
            ]);
        } else {
            $crearCliente = $conexion->prepare(
                "INSERT INTO cliente (nombre, apellido, telefono, email, password_hash)
                 VALUES (:nombre, :apellido, :telefono, :email, :password_hash)"
            );
            $crearCliente->execute([
                "nombre" => $nombre,
                "apellido" => $apellido,
                "telefono" => $telefono,
                "email" => $email,
                "password_hash" => password_hash($contrasena, PASSWORD_DEFAULT),
            ]);
            $idCliente = (int)$conexion->lastInsertId();
        }

        $conexion->commit();

        iniciarSesion([
            "id" => $idCliente,
            "rol" => "cliente",
            "nombre" => $nombre,
            "apellido" => $apellido,
        ]);
    } catch (PDOException $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        if ($error->getCode() === "23000") {
            responderSesion(409, ["error" => "Ya existe una cuenta con ese correo."]);
        }
        error_log("Error al registrar cuenta de cliente en BARBER x: " . $error->getMessage());
        responderSesion(500, ["error" => "No fue posible crear la cuenta. Verifica la base de datos."]);
    }
}

responderSesion(400, ["error" => "La acción solicitada no es válida."]);
