<?php
require_once __DIR__ . "/calidad_logger.php";
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();

function responderCalidad(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function leerDatosCalidad(): array
{
    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responderCalidad(400, ["error" => "Los datos enviados no son válidos."]);
    }
    return $datos;
}

function leerLogServidor(string $fuente, string $ruta): array
{
    if ($ruta === "" || !is_file($ruta) || !is_readable($ruta)) {
        return ["fuente" => $fuente, "disponible" => false, "error" => "El archivo de log no existe o no se puede leer.", "entradas" => []];
    }

    $archivo = fopen($ruta, "rb");
    if ($archivo === false) {
        return ["fuente" => $fuente, "disponible" => false, "error" => "No fue posible abrir el archivo de log.", "entradas" => []];
    }

    $informacion = fstat($archivo);
    if ($informacion === false || !isset($informacion["size"])) {
        fclose($archivo);
        return ["fuente" => $fuente, "disponible" => false, "error" => "No fue posible consultar el tamaño del archivo de log.", "entradas" => []];
    }
    $tamano = (int)$informacion["size"];
    $inicio = max(0, $tamano - 131072);
    if ($inicio > 0 && fseek($archivo, $inicio) !== 0) {
        fclose($archivo);
        return ["fuente" => $fuente, "disponible" => false, "error" => "No fue posible leer el archivo de log.", "entradas" => []];
    }
    if ($inicio > 0) {
        fgets($archivo);
    }
    $lineas = [];
    while (($linea = fgets($archivo)) !== false) {
        $linea = trim($linea);
        if ($linea !== "") {
            $lineas[] = $linea;
        }
    }
    $lecturaCompleta = feof($archivo);
    fclose($archivo);
    if (!$lecturaCompleta) {
        return ["fuente" => $fuente, "disponible" => false, "error" => "Ocurrió un error al leer el archivo de log.", "entradas" => []];
    }

    $entradas = [];
    foreach (array_slice($lineas, -100) as $linea) {
        preg_match('/^\[([^\]]+)\]/', $linea, $coincidencia);
        $detalle = sanitizarDetalleCalidad($linea);
        $entradas[] = [
            "fuente" => $fuente,
            "fecha" => $coincidencia[1] ?? "",
            "detalle" => $detalle,
        ];
    }

    return ["fuente" => $fuente, "disponible" => true, "error" => null, "entradas" => array_reverse($entradas)];
}

$usuario = $_SESSION["usuario"] ?? null;
if (!is_array($usuario) || !in_array($usuario["rol"] ?? "", ["admin", "cliente"], true)) {
    responderCalidad(401, ["error" => "Inicia sesión con una cuenta autorizada para enviar sugerencias o consultar calidad."]);
}
session_write_close();

try {
    require __DIR__ . "/../conexionBD.php";
    $metodo = $_SERVER["REQUEST_METHOD"];

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

        $phpLog = (string)ini_get("error_log");
        $documentRoot = realpath((string)($_SERVER["DOCUMENT_ROOT"] ?? ""));
        $apacheLog = (string)(getenv("BARBER_X_APACHE_ERROR_LOG") ?: (
            $documentRoot === false
                ? ""
                : dirname($documentRoot) . DIRECTORY_SEPARATOR . "apache" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "error.log"
        ));
        $logsServidor = [
            leerLogServidor("PHP", $phpLog),
            leerLogServidor("Apache", $apacheLog),
        ];
        $resumen["logs_servidor"] = array_sum(array_map(
            static fn(array $fuente): int => count($fuente["entradas"]),
            $logsServidor
        ));
        responderCalidad(200, [
            "registros" => $registros,
            "resumen" => $resumen,
            "logs_servidor" => $logsServidor,
        ]);
    }

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
            $tipo = "sugerencia";
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
    error_log("Error en el módulo de calidad de BARBER X: " . $error->getMessage());
    responderCalidad(500, ["error" => "No fue posible completar la operación de calidad. Verifica la base de datos."]);
}
