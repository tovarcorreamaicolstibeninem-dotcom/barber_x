<?php
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();
require_once __DIR__ . "/calidad_logger.php";

function responderVideo(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION["usuario"])) {
    responderVideo(401, ["error" => "Inicia sesión para consultar el video explicativo."]);
}

$metodo = $_SERVER["REQUEST_METHOD"];
$esAdmin = ($_SESSION["usuario"]["rol"] ?? "") === "admin";
if ($metodo !== "GET" && !$esAdmin) {
    responderVideo(403, ["error" => "Solo el administrador puede editar o eliminar el video."]);
}
session_write_close();

try {
    require __DIR__ . "/../conexionBD.php";
    $directorioVideos = __DIR__ . "/../videos";

    if ($metodo === "GET") {
        $video = $conexion->query(
            "SELECT titulo, descripcion, archivo FROM video_tutorial WHERE id_video = 1"
        )->fetch();
        $archivo = $video["archivo"] ?? null;
        $rutaVideo = $archivo !== null ? $directorioVideos . "/" . $archivo : null;
        $disponible = $archivo !== null
            && preg_match('/\Atutorial-[a-f0-9]{24}\.mp4\z|\Atutorial\.mp4\z/', $archivo) === 1
            && is_file($rutaVideo);
        responderVideo(200, [
            "titulo" => $video["titulo"] ?? "",
            "descripcion" => $video["descripcion"] ?? "",
            "video_url" => $disponible ? "videos/" . rawurlencode($archivo) : null,
        ]);
    }

    if (!$esAdmin) {
        responderVideo(403, ["error" => "Solo el administrador puede editar o eliminar el video."]);
    }

    if ($metodo === "POST") {
        $limiteBytes = 220 * 1024 * 1024;
        $longitudSolicitud = (int)($_SERVER["CONTENT_LENGTH"] ?? 0);
        if ($longitudSolicitud > $limiteBytes + 1024 * 1024) {
            responderVideo(413, ["error" => "El video supera el límite de 220 MB."]);
        }

        $titulo = trim((string)($_POST["titulo"] ?? ""));
        $descripcion = trim((string)($_POST["descripcion"] ?? ""));
        $longitudTitulo = function_exists("mb_strlen") ? mb_strlen($titulo, "UTF-8") : strlen($titulo);
        $longitudDescripcion = function_exists("mb_strlen") ? mb_strlen($descripcion, "UTF-8") : strlen($descripcion);
        if ($titulo === "" || $longitudTitulo > 120 || $longitudDescripcion > 2000) {
            responderVideo(422, ["error" => "Escribe un título de hasta 120 caracteres y una descripción de hasta 2.000 caracteres."]);
        }

        $existente = $conexion->query(
            "SELECT archivo FROM video_tutorial WHERE id_video = 1"
        )->fetch();
        $archivoAnterior = $existente["archivo"] ?? null;
        $archivoNuevo = null;
        $carga = $_FILES["video"] ?? null;

        if ($carga !== null && $carga["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($carga["error"] !== UPLOAD_ERR_OK) {
                $mensaje = match ($carga["error"]) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "El video supera el límite de 220 MB.",
                    UPLOAD_ERR_PARTIAL => "El video no se cargó completo. Inténtalo otra vez.",
                    UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => "El servidor no pudo guardar el archivo temporal.",
                    default => "No se pudo recibir el video. Inténtalo otra vez.",
                };
                responderVideo(422, ["error" => $mensaje]);
            }
            if ((int)$carga["size"] > $limiteBytes) {
                responderVideo(413, ["error" => "El video supera el límite de 220 MB."]);
            }

            $extension = strtolower(pathinfo((string)$carga["name"], PATHINFO_EXTENSION));
            $archivoTemporal = (string)$carga["tmp_name"];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $tipoMime = $finfo->file($archivoTemporal);
            if ($extension !== "mp4" || $tipoMime !== "video/mp4") {
                responderVideo(422, ["error" => "Selecciona un archivo MP4 válido."]);
            }

            if (!is_dir($directorioVideos) && !mkdir($directorioVideos, 0755, true) && !is_dir($directorioVideos)) {
                registrarErrorAplicacion($conexion ?? null, "Almacenamiento de video", "No se pudo crear el directorio de videos de BARBER x.");
                responderVideo(500, ["error" => "No fue posible preparar el almacenamiento del video."]);
            }
            $archivoNuevo = "tutorial-" . bin2hex(random_bytes(12)) . ".mp4";
            if (!move_uploaded_file($archivoTemporal, $directorioVideos . "/" . $archivoNuevo)) {
                responderVideo(500, ["error" => "No fue posible guardar el video cargado."]);
            }
        } elseif ($archivoAnterior === null) {
            responderVideo(422, ["error" => "Selecciona un archivo MP4 para agregar el video."]);
        }

        try {
            $guardar = $conexion->prepare(
                "INSERT INTO video_tutorial (id_video, titulo, descripcion, archivo)
                 VALUES (1, :titulo, :descripcion, :archivo)
                 ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), descripcion = VALUES(descripcion),
                     archivo = COALESCE(VALUES(archivo), archivo)"
            );
            $guardar->execute([
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "archivo" => $archivoNuevo,
            ]);
        } catch (PDOException $error) {
            if ($archivoNuevo !== null) {
                unlink($directorioVideos . "/" . $archivoNuevo);
            }
            throw $error;
        }

        if ($archivoNuevo !== null && $archivoAnterior !== null
            && preg_match('/\Atutorial-[a-f0-9]{24}\.mp4\z|\Atutorial\.mp4\z/', $archivoAnterior) === 1
            && is_file($directorioVideos . "/" . $archivoAnterior)
            && !unlink($directorioVideos . "/" . $archivoAnterior)) {
            registrarErrorAplicacion($conexion ?? null, "Eliminación de video anterior", "No se pudo eliminar el video anterior de BARBER x: " . $archivoAnterior);
            responderVideo(500, ["error" => "El video se actualizó, pero no se pudo eliminar el archivo anterior del servidor."]);
        }
        responderVideo(200, ["mensaje" => "El video explicativo fue actualizado."]);
    }

    if ($metodo === "DELETE") {
        $existente = $conexion->query(
            "SELECT archivo FROM video_tutorial WHERE id_video = 1"
        )->fetch();
        $conexion->exec("DELETE FROM video_tutorial WHERE id_video = 1");
        $archivoAnterior = $existente["archivo"] ?? null;
        if ($archivoAnterior !== null
            && preg_match('/\Atutorial-[a-f0-9]{24}\.mp4\z|\Atutorial\.mp4\z/', $archivoAnterior) === 1
            && is_file($directorioVideos . "/" . $archivoAnterior)
            && !unlink($directorioVideos . "/" . $archivoAnterior)) {
            registrarErrorAplicacion($conexion ?? null, "Eliminación de video", "No se pudo eliminar el video de BARBER x: " . $archivoAnterior);
            responderVideo(500, ["error" => "El registro se eliminó, pero el archivo de video no pudo borrarse del servidor."]);
        }
        responderVideo(200, ["mensaje" => "El video explicativo fue eliminado."]);
    }

    header("Allow: GET, POST, DELETE");
    responderVideo(405, ["error" => "Método no permitido."]);
} catch (PDOException $error) {
    registrarErrorAplicacion($conexion ?? null, "Gestión de video", "Error en el video de BARBER x: " . $error->getMessage());
    responderVideo(500, ["error" => "No fue posible gestionar el video. Verifica la base de datos."]);
}
