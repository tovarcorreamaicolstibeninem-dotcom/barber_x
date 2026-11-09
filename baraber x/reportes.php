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

function obtenerReporte(PDO $conexion): array
{
    $resumen = $conexion->query(
        "SELECT COUNT(*) AS total,
                SUM(LOWER(c.estado) IN ('pendiente', 'confirmada')) AS pendientes,
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
    $actividadMensual = $conexion->query(
        "SELECT DATE_FORMAT(fecha_hora, '%Y-%m') AS mes,
                COUNT(*) AS citas,
                SUM(LOWER(estado) = 'cancelada') AS canceladas
         FROM cita
         WHERE fecha_hora >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
           AND fecha_hora < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         GROUP BY DATE_FORMAT(fecha_hora, '%Y-%m')
         ORDER BY mes"
    )->fetchAll();
    $clientesRegistradosSemana = $conexion->query(
        "SELECT DATE_FORMAT(fecha_registro, '%Y-%m-%d') AS fecha, COUNT(*) AS total
         FROM cliente
         WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
           AND fecha_registro < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         GROUP BY fecha_registro
         ORDER BY fecha_registro"
    )->fetchAll();
    $clientesRegistradosMes = $conexion->query(
        "SELECT DATE_FORMAT(fecha_registro, '%Y-%m-%d') AS fecha, COUNT(*) AS total
         FROM cliente
         WHERE fecha_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND fecha_registro < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         GROUP BY fecha_registro
         ORDER BY fecha_registro"
    )->fetchAll();

    return [
        "citas" => [
            "total" => (int)$resumen["total"],
            "pendientes" => (int)($resumen["pendientes"] ?? 0),
            "completadas" => (int)($resumen["completadas"] ?? 0),
            "canceladas" => (int)($resumen["canceladas"] ?? 0),
        ],
        "ingresos" => (float)$resumen["ingresos"],
        "clientes" => (int)$clientes["total"],
        "registros_clientes" => [
            "semana" => array_map(static fn(array $registro): array => [
                "fecha" => (string)$registro["fecha"],
                "total" => (int)$registro["total"],
            ], $clientesRegistradosSemana),
            "mes" => array_map(static fn(array $registro): array => [
                "fecha" => (string)$registro["fecha"],
                "total" => (int)$registro["total"],
            ], $clientesRegistradosMes),
        ],
        "actividad_mensual" => array_map(static fn(array $mes): array => [
            "mes" => (string)$mes["mes"],
            "citas" => (int)$mes["citas"],
            "canceladas" => (int)($mes["canceladas"] ?? 0),
        ], $actividadMensual),
        "servicios" => array_map(static fn(array $servicio): array => [
            "servicio" => (string)$servicio["servicio"],
            "citas" => (int)$servicio["citas"],
            "completadas" => (int)$servicio["completadas"],
            "ingresos" => (float)$servicio["ingresos"],
        ], $porServicio),
    ];
}

function celdaExcel(string|int|float $valor, string $tipo = "String", ?string $estilo = null): string
{
    $atributos = ' ss:StyleID="' . ($estilo ?? "Default") . '"';
    $contenido = $tipo === "String"
        ? htmlspecialchars((string)$valor, ENT_QUOTES | ENT_XML1, "UTF-8")
        : (string)$valor;
    return '<Cell' . $atributos . '><Data ss:Type="' . $tipo . '">' . $contenido . '</Data></Cell>';
}

function filaExcel(array $celdas, ?int $combinar = null): string
{
    $atributo = $combinar !== null ? ' ss:MergeAcross="' . $combinar . '"' : "";
    return '<Row' . $atributo . '>' . implode("", $celdas) . '</Row>';
}

function generarLibroExcel(array $reporte, string $fecha, string $administrador): string
{
    $resumen = [
        filaExcel([celdaExcel("BARBER X - REPORTE DE GESTIÓN", "String", "Title")], 1),
        filaExcel([celdaExcel("Fecha de generación", "String", "Header"), celdaExcel($fecha)]),
        filaExcel([celdaExcel("Generado por", "String", "Header"), celdaExcel($administrador)]),
        filaExcel([]),
        filaExcel([celdaExcel("Indicador", "String", "Header"), celdaExcel("Resultado", "String", "Header")]),
        filaExcel([celdaExcel("Citas totales"), celdaExcel($reporte["citas"]["total"], "Number", "Integer")]),
        filaExcel([celdaExcel("Citas pendientes"), celdaExcel($reporte["citas"]["pendientes"], "Number", "Integer")]),
        filaExcel([celdaExcel("Citas completadas"), celdaExcel($reporte["citas"]["completadas"], "Number", "Integer")]),
        filaExcel([celdaExcel("Citas canceladas"), celdaExcel($reporte["citas"]["canceladas"], "Number", "Integer")]),
        filaExcel([celdaExcel("Clientes registrados"), celdaExcel($reporte["clientes"], "Number", "Integer")]),
        filaExcel([celdaExcel("Ingresos de citas completadas"), celdaExcel($reporte["ingresos"], "Number", "Currency")]),
    ];

    $detalle = [
        filaExcel([
            celdaExcel("ACTIVIDAD POR SERVICIO", "String", "Title"),
        ], 3),
        filaExcel([
            celdaExcel("Servicio", "String", "Header"),
            celdaExcel("Citas agendadas", "String", "Header"),
            celdaExcel("Completadas", "String", "Header"),
            celdaExcel("Ingresos completados", "String", "Header"),
        ]),
    ];
    foreach ($reporte["servicios"] as $servicio) {
        $detalle[] = filaExcel([
            celdaExcel($servicio["servicio"]),
            celdaExcel($servicio["citas"], "Number", "Integer"),
            celdaExcel($servicio["completadas"], "Number", "Integer"),
            celdaExcel($servicio["ingresos"], "Number", "Currency"),
        ]);
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<?mso-application progid="Excel.Sheet"?>'
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
        . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
        . ' xmlns:x="urn:schemas-microsoft-com:office:excel"'
        . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'
        . ' xmlns:html="http://www.w3.org/TR/REC-html40">'
        . '<Styles>'
        . '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/>'
        . '<Borders/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#222222"/>'
        . '<Interior/><NumberFormat/><Protection/></Style>'
        . '<Style ss:ID="Title"><Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/>'
        . '<Interior ss:Color="#282318" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/>'
        . '<Interior ss:Color="#9A7738" ss:Pattern="Solid"/></Style>'
        . '<Style ss:ID="Integer"><NumberFormat ss:Format="0"/></Style>'
        . '<Style ss:ID="Currency"><NumberFormat ss:Format="&quot;$&quot;#,##0.00"/></Style>'
        . '</Styles>'
        . '<Worksheet ss:Name="Resumen"><Table>'
        . '<Column ss:Width="230"/><Column ss:Width="190"/>'
        . implode("", $resumen)
        . '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
        . '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>5</SplitHorizontal><TopRowBottomPane>5</TopRowBottomPane>'
        . '<ActivePane>2</ActivePane></WorksheetOptions></Worksheet>'
        . '<Worksheet ss:Name="Por servicio"><Table>'
        . '<Column ss:Width="240"/><Column ss:Width="120"/><Column ss:Width="120"/><Column ss:Width="180"/>'
        . implode("", $detalle)
        . '</Table></Worksheet>'
        . '<Worksheet ss:Name="Información"><Table><Column ss:Width="240"/><Column ss:Width="420"/>'
        . filaExcel([celdaExcel("Informe BARBER X", "String", "Title")], 1)
        . filaExcel([celdaExcel("Generado el"), celdaExcel($fecha)])
        . filaExcel([celdaExcel("Generado por"), celdaExcel($administrador)])
        . filaExcel([celdaExcel("Contenido"), celdaExcel("Resumen de citas, clientes, ingresos y actividad por servicio.")])
        . '</Table></Worksheet></Workbook>';
}

if (!isset($_SESSION["usuario"]) || ($_SESSION["usuario"]["rol"] ?? "") !== "admin") {
    responderReportes(403, ["error" => "Debes iniciar sesión como administrador para consultar los reportes."]);
}
session_write_close();

try {
    require __DIR__ . "/../conexionBD.php";
    $metodo = $_SERVER["REQUEST_METHOD"];
    $id = $_GET["id"] ?? null;

    if ($metodo === "GET" && $id !== null) {
        if (!ctype_digit((string)$id) || (int)$id < 1) {
            responderReportes(400, ["error" => "El identificador del reporte no es válido."]);
        }
        $consulta = $conexion->prepare(
            "SELECT generado_en, generado_por, datos_json FROM reporte_generado WHERE id_reporte = :id"
        );
        $consulta->execute(["id" => (int)$id]);
        $reporteGuardado = $consulta->fetch();
        if (!$reporteGuardado) {
            responderReportes(404, ["error" => "No se encontró el reporte solicitado."]);
        }
        $datos = json_decode($reporteGuardado["datos_json"], true);
        if (!is_array($datos) || !isset($datos["citas"], $datos["servicios"], $datos["clientes"], $datos["ingresos"])) {
            error_log("Datos inválidos en el reporte BARBER X #" . $id);
            responderReportes(500, ["error" => "No fue posible leer los datos guardados de este reporte."]);
        }
        $archivo = "reporte-barber-x-" . (int)$id . ".xls";
        $libro = generarLibroExcel($datos, $reporteGuardado["generado_en"], $reporteGuardado["generado_por"]);
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header("Content-Length: " . strlen($libro));
        echo $libro;
        exit;
    }

    if ($metodo === "GET" && ($_GET["accion"] ?? "") === "lista") {
        $filas = $conexion->query(
            "SELECT id_reporte, generado_en, generado_por, total_citas, total_clientes, ingresos
             FROM reporte_generado
             ORDER BY generado_en DESC, id_reporte DESC
             LIMIT 100"
        )->fetchAll();
        responderReportes(200, ["reportes" => array_map(static fn(array $fila): array => [
            "id" => (int)$fila["id_reporte"],
            "generado_en" => $fila["generado_en"],
            "generado_por" => $fila["generado_por"],
            "total_citas" => (int)$fila["total_citas"],
            "total_clientes" => (int)$fila["total_clientes"],
            "ingresos" => (float)$fila["ingresos"],
        ], $filas)]);
    }

    if ($metodo === "GET" && !isset($_GET["accion"])) {
        responderReportes(200, obtenerReporte($conexion));
    }

    if ($metodo === "POST") {
        $datosSolicitud = json_decode(file_get_contents("php://input"), true);
        if (!is_array($datosSolicitud) || ($datosSolicitud["accion"] ?? "") !== "crear") {
            responderReportes(400, ["error" => "La acción para generar el reporte no es válida."]);
        }
        $reporte = obtenerReporte($conexion);
        $generadoPor = trim(
            (string)($_SESSION["usuario"]["nombre"] ?? "Administrador")
            . " "
            . (string)($_SESSION["usuario"]["apellido"] ?? "")
        );
        $guardar = $conexion->prepare(
            "INSERT INTO reporte_generado (generado_por, total_citas, total_clientes, ingresos, datos_json)
             VALUES (:generado_por, :total_citas, :total_clientes, :ingresos, :datos_json)"
        );
        $guardar->execute([
            "generado_por" => $generadoPor !== "" ? $generadoPor : "Administrador",
            "total_citas" => $reporte["citas"]["total"],
            "total_clientes" => $reporte["clientes"],
            "ingresos" => $reporte["ingresos"],
            "datos_json" => json_encode($reporte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
        responderReportes(201, ["id" => (int)$conexion->lastInsertId(), "mensaje" => "El reporte se guardó correctamente."]);
    }

    header("Allow: GET, POST");
    responderReportes(405, ["error" => "Método no permitido."]);
} catch (PDOException $error) {
    error_log("Error al gestionar reportes de BARBER x: " . $error->getMessage());
    responderReportes(500, ["error" => "No fue posible gestionar los reportes. Verifica la base de datos."]);
} catch (JsonException $error) {
    error_log("No se pudieron serializar los datos del reporte de BARBER x: " . $error->getMessage());
    responderReportes(500, ["error" => "No fue posible guardar los datos del reporte."]);
}
