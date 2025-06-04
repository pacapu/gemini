<?php
// Iniciar la sesión si aún no está iniciada (esto debería estar en la primera línea de cada script que use sesiones)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Stock - Gemini</title>
    <link rel="stylesheet" href="/stock_gemini/css/style.css">
    <script src="/stock_gemini/js/script.js" defer></script>
</head>
<body>
    <header>
        <div class="logo">
            <h1>StockApp</h1>
        </div>
        <nav>
            <ul>
                <li><a href="/stock_gemini/index.php">Dashboard</a></li>
                <li><a href="/stock_gemini/views/busqueda_avanzada.php">Búsqueda</a></li>
                <li class="dropdown">
                    <a href="/stock_gemini/views/productos/listar.php" class="dropbtn">Productos</a>
                    <div class="dropdown-content">
                        <a href="/stock_gemini/views/productos/buscar.php">Búsqueda de Producto</a>
                        <a href="/stock_gemini/views/productos/nuevo.php">Nuevo Producto</a>
                        <a href="/stock_gemini/views/productos/modificar.php">Modificación de Productos</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="/stock_gemini/views/almacenes/listar.php" class="dropbtn">Almacenes</a>
                    <div class="dropdown-content">
                        <a href="/stock_gemini/views/almacenes/buscar.php">Búsqueda Almacén</a>
                        <a href="/stock_gemini/views/almacenes/nuevo.php">Nuevo Almacén</a>
                        <a href="/stock_gemini/views/almacenes/modificar.php">Modificación de Almacén</a>
                        <a href="/stock_gemini/views/almacenes/configurar_principal.php">Configuración Almacén Principal</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Usuarios</a>
                    <div class="dropdown-content">
                        <a href="/stock_gemini/views/usuarios/nuevo.php">Nuevo Usuario</a>
                        <a href="/stock_gemini/views/usuarios/listar.php">Modificación de Usuarios</a>
                    </div>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/stock_gemini/views/logout.php">Salir (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>