<?php
// Configuración de la base de datos
$host = 'localhost';
$db = 'stock_gemini';
$user = 'root'; // Usuario que configuraste en phpMyAdmin
$pass = 'root'; // Contraseña que configuraste en phpMyAdmin
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
