<?php
// Prepara el endpoint de reportes para responder JSON y usar una sesión protegida.
header("Content-Type: application/json; charset=utf-8");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();
require_once __DIR__ . "/calidad_logger.php";

// Envía una respuesta JSON y termina la ejecución del endpoint.
function responderReportes(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Consulta los indicadores generales, la actividad por servicio y los registros de clientes.
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

// Calcula ingresos completados para el día, semana de lunes a domingo y mes de referencia.
function obtenerIngresosPorPeriodo(PDO $conexion, ?string $fechaReferencia = null): array
{
    $fechaBase = $fechaReferencia === null ? "CURDATE()" : "CAST(:fecha_referencia AS DATE)";
    $consulta = $conexion->prepare(
        "SELECT DATE_FORMAT(periodo.fecha_referencia, '%Y-%m-%d') AS fecha_referencia,
                DATE_FORMAT(periodo.fecha_referencia, '%d/%m/%Y') AS rango_dia,
                CONCAT(
                    DATE_FORMAT(DATE_SUB(periodo.fecha_referencia, INTERVAL WEEKDAY(periodo.fecha_referencia) DAY), '%d/%m/%Y'),
                    ' - ',
                    DATE_FORMAT(DATE_ADD(DATE_SUB(periodo.fecha_referencia, INTERVAL WEEKDAY(periodo.fecha_referencia) DAY), INTERVAL 6 DAY), '%d/%m/%Y')
                ) AS rango_semana,
                DATE_FORMAT(periodo.fecha_referencia, '%m/%Y') AS rango_mes,
                COALESCE(SUM(
                    CASE WHEN cita.fecha_hora >= periodo.fecha_referencia
                              AND cita.fecha_hora < DATE_ADD(periodo.fecha_referencia, INTERVAL 1 DAY)
                         THEN COALESCE(cita.precio, servicios.precio_calculado, 0) ELSE 0 END
                ), 0) AS ingresos_dia,
                COALESCE(SUM(
                    CASE WHEN cita.fecha_hora >= DATE_SUB(periodo.fecha_referencia, INTERVAL WEEKDAY(periodo.fecha_referencia) DAY)
                         THEN COALESCE(cita.precio, servicios.precio_calculado, 0) ELSE 0 END
                ), 0) AS ingresos_semana,
                COALESCE(SUM(COALESCE(cita.precio, servicios.precio_calculado, 0)), 0) AS ingresos_mes
         FROM (SELECT $fechaBase AS fecha_referencia) periodo
         LEFT JOIN cita ON LOWER(cita.estado) = 'completada'
             AND cita.fecha_hora >= DATE_FORMAT(periodo.fecha_referencia, '%Y-%m-01')
             AND cita.fecha_hora < DATE_ADD(periodo.fecha_referencia, INTERVAL 1 DAY)
         LEFT JOIN (
             SELECT cs.id_cita,
                    SUM(COALESCE(cs.precio, s.precio) * COALESCE(cs.cantidad, 1)) AS precio_calculado
             FROM cita_servicio cs
             INNER JOIN servicio s ON s.id_serv = cs.id_serv
             GROUP BY cs.id_cita
         ) servicios ON servicios.id_cita = cita.id_cita"
    );
    if ($fechaReferencia !== null) {
        $consulta->execute(["fecha_referencia" => $fechaReferencia]);
    } else {
        $consulta->execute();
    }
    $ingresos = $consulta->fetch();

    return [
        "fecha_referencia" => (string)$ingresos["fecha_referencia"],
        "dia" => [
            "rango" => (string)$ingresos["rango_dia"],
            "ingresos" => (float)$ingresos["ingresos_dia"],
        ],
        "semana" => [
            "rango" => (string)$ingresos["rango_semana"],
            "ingresos" => (float)$ingresos["ingresos_semana"],
        ],
        "mes" => [
            "rango" => (string)$ingresos["rango_mes"],
            "ingresos" => (float)$ingresos["ingresos_mes"],
        ],
    ];
}

// Escapa el valor de una celda y agrega el tipo y estilo que entiende Excel XML.
function celdaExcel(string|int|float $valor, string $tipo = "String", ?string $estilo = null): string
{
    $atributos = ' ss:StyleID="' . ($estilo ?? "Default") . '"';
    $contenido = $tipo === "String"
        ? htmlspecialchars((string)$valor, ENT_QUOTES | ENT_XML1, "UTF-8")
        : (string)$valor;
    return '<Cell' . $atributos . '><Data ss:Type="' . $tipo . '">' . $contenido . '</Data></Cell>';
}

// Convierte una lista de celdas en una fila del libro y puede combinar columnas.
function filaExcel(array $celdas, ?int $combinar = null): string
{
    $atributo = $combinar !== null ? ' ss:MergeAcross="' . $combinar . '"' : "";
    return '<Row' . $atributo . '>' . implode("", $celdas) . '</Row>';
}

// Construye el libro Excel XML con el resumen, los servicios y los ingresos por periodo.
function generarLibroExcel(array $reporte, string $fecha, string $administrador): string
{
    // Hoja de indicadores generales del negocio.
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

    // Hoja con citas, servicios completados e ingresos desglosados por servicio.
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

    // Hoja independiente con los totales del día, semana y mes.
    $ingresosPeriodo = $reporte["ingresos_periodo"];
    $periodos = [
        filaExcel([celdaExcel("INGRESOS POR PERIODO", "String", "Title")], 2),
        filaExcel([celdaExcel("Fecha de referencia", "String", "Header"), celdaExcel($ingresosPeriodo["fecha_referencia"]), celdaExcel("Solo citas completadas")]),
        filaExcel([]),
        filaExcel([
            celdaExcel("Periodo", "String", "Header"),
            celdaExcel("Rango", "String", "Header"),
            celdaExcel("Ingresos", "String", "Header"),
        ]),
        filaExcel([celdaExcel("Día"), celdaExcel($ingresosPeriodo["dia"]["rango"]), celdaExcel($ingresosPeriodo["dia"]["ingresos"], "Number", "Currency")]),
        filaExcel([celdaExcel("Semana (lunes a domingo)"), celdaExcel($ingresosPeriodo["semana"]["rango"]), celdaExcel($ingresosPeriodo["semana"]["ingresos"], "Number", "Currency")]),
        filaExcel([celdaExcel("Mes"), celdaExcel($ingresosPeriodo["mes"]["rango"]), celdaExcel($ingresosPeriodo["mes"]["ingresos"], "Number", "Currency")]),
    ];

    // Empaqueta el XML SpreadsheetML para que Excel abra el informe como libro.
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
        . '<Worksheet ss:Name="Ingresos por periodo"><Table>'
        . '<Column ss:Width="230"/><Column ss:Width="200"/><Column ss:Width="180"/>'
        . implode("", $periodos)
        . '</Table></Worksheet>'
        . '<Worksheet ss:Name="Información"><Table><Column ss:Width="240"/><Column ss:Width="420"/>'
        . filaExcel([celdaExcel("Informe BARBER X", "String", "Title")], 1)
        . filaExcel([celdaExcel("Generado el"), celdaExcel($fecha)])
        . filaExcel([celdaExcel("Generado por"), celdaExcel($administrador)])
        . filaExcel([celdaExcel("Contenido"), celdaExcel("Resumen de citas, clientes e ingresos diarios, semanales y mensuales, además de actividad por servicio.")])
        . '</Table></Worksheet></Workbook>';
}

// Limita la consulta y generación de informes al rol de administración.
if (!isset($_SESSION["usuario"]) || ($_SESSION["usuario"]["rol"] ?? "") !== "admin") {
    responderReportes(403, ["error" => "Debes iniciar sesión como administrador para consultar los reportes."]);
}
session_write_close();

try {
    require __DIR__ . "/../conexionBD.php";
    $metodo = $_SERVER["REQUEST_METHOD"];
    $id = $_GET["id"] ?? null;

    // Recupera un informe guardado y lo entrega como archivo descargable de Excel.
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
            registrarErrorAplicacion($conexion ?? null, "Reporte inválido", "Datos inválidos en el reporte BARBER X #" . $id);
            responderReportes(500, ["error" => "No fue posible leer los datos guardados de este reporte."]);
        }
        if (!isset($datos["ingresos_periodo"])) {
            $datos["ingresos_periodo"] = obtenerIngresosPorPeriodo(
                $conexion,
                substr((string)$reporteGuardado["generado_en"], 0, 10)
            );
        }
        $archivo = "reporte-barber-x-" . (int)$id . ".xls";
        $libro = generarLibroExcel($datos, $reporteGuardado["generado_en"], $reporteGuardado["generado_por"]);
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header("Content-Length: " . strlen($libro));
        echo $libro;
        exit;
    }

    // Devuelve el historial de informes para la tabla administrativa.
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

    // Presenta los indicadores actuales sin guardar un nuevo informe.
    if ($metodo === "GET" && !isset($_GET["accion"])) {
        responderReportes(200, obtenerReporte($conexion));
    }

    // Guarda una fotografía de los indicadores y sus ingresos por periodo.
    if ($metodo === "POST") {
        $datosSolicitud = json_decode(file_get_contents("php://input"), true);
        if (!is_array($datosSolicitud) || ($datosSolicitud["accion"] ?? "") !== "crear") {
            responderReportes(400, ["error" => "La acción para generar el reporte no es válida."]);
        }
        $reporte = obtenerReporte($conexion);
        $reporte["ingresos_periodo"] = obtenerIngresosPorPeriodo($conexion);
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
    registrarErrorAplicacion($conexion ?? null, "Gestión de reportes", "Error al gestionar reportes de BARBER x: " . $error->getMessage());
    responderReportes(500, ["error" => "No fue posible gestionar los reportes. Verifica la base de datos."]);
} catch (JsonException $error) {
    registrarErrorAplicacion($conexion ?? null, "Serialización de reporte", "No se pudieron serializar los datos del reporte de BARBER x: " . $error->getMessage());
    responderReportes(500, ["error" => "No fue posible guardar los datos del reporte."]);
}
