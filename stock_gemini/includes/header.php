<?php
// includes/header.php

// Iniciar la sesión si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- IMPORTANTE: AJUSTA ESTA LÍNEA SEGÚN LA UBICACIÓN DE TU PROYECTO ---
// Si tu proyecto se accede como http://localhost/stock_gemini/
$base_path = '/stock_gemini/';
// Si tu proyecto se accede directamente desde la raíz de tu servidor web (ej. http://localhost/)
// $base_path = '/';
// -----------------------------------------------------------------------

// Redirigir si no hay sesión iniciada (como en header_preferido.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . 'login.php');
    exit();
}

// Aquí usamos 'user_type' como lo tienes definido (admin/normal)
$user_type = $_SESSION['user_type'] ?? 'normal'; // Default para evitar errores
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Stock - Gemini</title>
    
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="<?php echo $base_path; ?>js/script.js" defer></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $base_path; ?>index.php">StockApp</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>index.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>views/busqueda_avanzada.php">Búsqueda</a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownProductos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Productos
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownProductos">
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/productos/listar.php">Listar Productos</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/productos/buscar.php">Búsqueda de Producto</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/productos/nuevo.php">Nuevo Producto</a></li>
                                </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAlmacenes" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Almacenes
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownAlmacenes">
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/almacenes/listar.php">Listar Almacenes</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/almacenes/buscar.php">Búsqueda Almacén</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/almacenes/nuevo.php">Nuevo Almacén</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/almacenes/configurar_principal.php">Configuración Almacén Principal</a></li>
                            </ul>
                        </li>

                        <?php if ($user_type === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUsuarios" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Usuarios
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownUsuarios">
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/usuarios/listar.php">Listar Usuarios</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/usuarios/crear.php">Nuevo Usuario</a></li>
                                </ul>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <span class="nav-link text-white-50">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars(ucfirst($user_type)); ?>)</span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-danger ms-2" href="<?php echo $base_path; ?>logout.php">Salir</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container mt-4">
</body>
</html>