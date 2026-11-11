<?php

// Oculta credenciales comunes y limita la longitud antes de guardar detalles técnicos.
function sanitizarDetalleCalidad(string $detalle): string
{
    $detalle = preg_replace(
        '/\b(password|contrasena|token|authorization|cookie)\b\s*[:=]\s*(?:(?:bearer|basic)\s+)?("[^"]*"|\'[^\']*\'|[^\s,;]+)/i',
        '$1=[oculto]',
        $detalle
    ) ?? $detalle;
    return function_exists("mb_substr")
        ? mb_substr($detalle, 0, 10000, "UTF-8")
        : substr($detalle, 0, 10000);
}

// Registra un error tanto en el historial de calidad como en el log estándar de PHP.
function registrarErrorAplicacion(?PDO $conexion, string $titulo, string $mensaje): void
{
    // Guarda el evento en MySQL cuando hay una conexión disponible.
    if ($conexion instanceof PDO) {
        try {
            $registro = $conexion->prepare(
                "INSERT INTO registro_calidad (tipo, origen, titulo, detalle, severidad)
                 VALUES ('error', 'aplicacion', :titulo, :detalle, 'alta')"
            );
            $registro->execute([
                "titulo" => function_exists("mb_substr")
                    ? mb_substr($titulo, 0, 120, "UTF-8")
                    : substr($titulo, 0, 120),
                "detalle" => sanitizarDetalleCalidad($mensaje),
            ]);
        } catch (PDOException $error) {
            error_log("No se pudo guardar el error de BARBER X en el registro de calidad: " . $error->getMessage());
        }
    }

    error_log($mensaje);
}
