<?php
// logout.php
session_start();

// Asegúrate de que $base_path esté definida aquí.
// Opción 1: Si tienes un archivo de configuración global con $base_path
// require_once 'includes/config.php'; 
// Opción 2: Definirla directamente si logout.php no incluye header.php
$base_path = '/stock_gemini/'; // Asegúrate que esto coincide con tu header.php

session_unset(); // Elimina todas las variables de sesión
session_destroy(); // Destruye la sesión

header("Location: " . $base_path . "views/login.php"); // Usando la variable
exit();
?>