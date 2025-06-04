<?php
session_start();
// Redirige al login si no hay sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit();
}

// Incluye la cabecera con el menú
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

$almacen_principal = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM almacenes WHERE es_principal_dashboard = TRUE LIMIT 1");
    $stmt->execute();
    $almacen_principal = $stmt->fetch();
} catch (PDOException $e) {
    // Manejar el error de la base de datos
    $_SESSION['error_message'] = "Error al cargar el almacén principal: " . $e->getMessage();
}
?>

<div class="container">
    <div class="dashboard-header">
        <h2>Dashboard</h2>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-content">
        <div class="principal-almacen-info">
            <h3>Almacén Principal</h3>
            <?php if ($almacen_principal): ?>
                <h4><?php echo htmlspecialchars($almacen_principal['nombre']); ?></h4>
                <p><?php echo htmlspecialchars($almacen_principal['descripcion']); ?></p>
            <?php else: ?>
                <p>No se ha configurado un almacén principal. Puedes configurarlo en <a href="views/almacenes/configurar_principal.php">Configuración de Almacén Principal</a>.</p>
            <?php endif; ?>
        </div>

        <div class="quick-search">
    <h3>Búsqueda Rápida</h3>
    <form action="views/productos/listar.php" method="GET">
        <input type="text" name="search" placeholder="Buscar por N° Inventario, Serie o Descripción" class="form-control">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
</div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>