<?php
$conexion = (static function (): PDO {
    $servidor = "localhost";
    $nombre_bd = "barber_x";
    $usuario_bd = "root";
    $contrasena_bd = "";
    $dsn = "mysql:host=$servidor;dbname=$nombre_bd;charset=utf8mb4";

    return new PDO($dsn, $usuario_bd, $contrasena_bd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
})();