<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$producto_id = $_GET['id'] ?? null;
$producto = null;
$movimientos = [];

if ($producto_id) {
    try {
        $stmt_producto = $pdo->prepare("SELECT p.*, a.nombre as nombre_almacen FROM productos p JOIN almacenes a ON p.id_almacen_actual = a.id_almacen WHERE id_producto = ?");
        $stmt_producto->execute([$producto_id]);
        $producto = $stmt_producto->fetch();

        if (!$producto) {
            $_SESSION['error_message'] = "Producto no encontrado.";
            header("Location: listar.php");
            exit();
        }

        $stmt_movimientos = $pdo->prepare("
            SELECT m.*, ua.nombre_usuario as usuario_alta, 
                   ao.nombre as almacen_origen_nombre, 
                   ad.nombre as almacen_destino_nombre
            FROM movimientos m
            LEFT JOIN usuarios ua ON m.id_usuario = ua.id_usuario
            LEFT JOIN almacenes ao ON m.id_almacen_origen = ao.id_almacen
            LEFT JOIN almacenes ad ON m.id_almacen_destino = ad.id_almacen
            WHERE m.id_producto = ? ORDER BY m.fecha_movimiento ASC
        ");
        $stmt_movimientos->execute([$producto_id]);
        $movimientos = $stmt_movimientos->fetchAll();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al cargar el producto o sus movimientos: " . $e->getMessage();
        header("Location: listar.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "ID de producto no especificado.";
    header("Location: listar.php");
    exit();
}
?>

<div class="container">
    <h2>Detalles del Producto: <?php echo htmlspecialchars($producto['descripcion']); ?></h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="product-details">
        <p><strong>N° Inventario:</strong> <?php echo htmlspecialchars($producto['numero_inventario']); ?></p>
        <p><strong>N° Serie:</strong> <?php echo htmlspecialchars($producto['numero_serie'] ?? 'N/A'); ?></p>
        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($producto['descripcion']); ?></p>
        <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($producto['cantidad']); ?></p>
        <p><strong>Tipo de Producto:</strong> <?php echo htmlspecialchars($producto['tipo_producto'] == 'unico' ? 'Único' : 'Genérico'); ?></p>
        <p><strong>Almacén Actual:</strong> <?php echo htmlspecialchars($producto['nombre_almacen']); ?></p>
        <p><strong>Último Movimiento:</strong> <?php echo htmlspecialchars($producto['fecha_ultimo_movimiento']); ?></p>
        <p><strong>Activo en Stock:</strong> <?php echo $producto['activo'] ? 'Sí' : 'No'; ?></p>
    </div>

    <h3>Historial de Movimientos</h3>
    <?php if (!empty($movimientos)): ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo de Movimiento</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Observaciones</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimientos as $movimiento): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($movimiento['fecha_movimiento']); ?></td>
                        <td>
                            <?php
                            echo htmlspecialchars($movimiento['tipo_movimiento']);
                            if ($movimiento['tipo_movimiento'] == 'alta') {
                                echo " (en " . htmlspecialchars($movimiento['almacen_destino_nombre']) . ")";
                            } elseif ($movimiento['tipo_movimiento'] == 'retirar') {
                                echo " (desde " . htmlspecialchars($movimiento['almacen_origen_nombre']) . ")";
                            } elseif ($movimiento['tipo_movimiento'] == 'mover_a' || $movimiento['tipo_movimiento'] == 'instalar' || $movimiento['tipo_movimiento'] == 'ingreso') {
                                echo " (de " . htmlspecialchars($movimiento['almacen_origen_nombre'] ?? 'N/A') . " a " . htmlspecialchars($movimiento['almacen_destino_nombre'] ?? 'N/A') . ")";
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($movimiento['almacen_origen_nombre'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($movimiento['almacen_destino_nombre'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($movimiento['observaciones']); ?></td>
                        <td><?php echo htmlspecialchars($movimiento['usuario_alta'] ?? 'Desconocido'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay movimientos registrados para este producto.</p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="modificar.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" class="btn btn-primary">Modificar Producto</a>
        <a href="listar.php" class="btn btn-secondary">Volver al Listado</a>
    </p>
</div>

<?php
require_once '../../includes/footer.php';
?>