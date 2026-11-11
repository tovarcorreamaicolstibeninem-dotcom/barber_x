<?php
// Crea una única conexión PDO para que los endpoints PHP consulten la base barber_x.
$conexion = (static function (): PDO {
    // Define las credenciales locales de MySQL; ajústalas si cambian en la instalación.
    $servidor = "localhost";
    $nombre_bd = "barber_x";
    $usuario_bd = "root";
    $contrasena_bd = "";
    // Fuerza la codificación UTF-8 completa para conservar correctamente los textos.
    $dsn = "mysql:host=$servidor;dbname=$nombre_bd;charset=utf8mb4";

    // Activa errores explícitos, resultados asociativos y consultas preparadas nativas.
    return new PDO($dsn, $usuario_bd, $contrasena_bd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
})();