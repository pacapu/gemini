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
        // Obtener detalles del producto
        $stmt_producto = $pdo->prepare("SELECT p.*, a.nombre as nombre_almacen FROM productos p JOIN almacenes a ON p.id_almacen_actual = a.id_almacen WHERE id_producto = ?");
        $stmt_producto->execute([$producto_id]);
        $producto = $stmt_producto->fetch();

        if (!$producto) {
            $_SESSION['error_message'] = "Producto no encontrado.";
            header("Location: listar.php");
            exit();
        }

        // Obtener historial de movimientos del producto
        // CAMBIO AQUÍ: Usamos 'm.id_movimiento' en lugar de 'm.fecha_movimiento'
        // Si tienes una columna de fecha en 'movimientos' (ej. 'fecha_movimiento_registro'), úsala en su lugar.
        $stmt_movimientos = $pdo->prepare("SELECT m.*, u.nombre_usuario,
                                        ao.nombre as nombre_almacen_origen,
                                        ad.nombre as nombre_almacen_destino
                                FROM movimientos m
                                JOIN usuarios u ON m.id_usuario = u.id_usuario
                                LEFT JOIN almacenes ao ON m.id_almacen_origen = ao.id_almacen
                                LEFT JOIN almacenes ad ON m.id_almacen_destino = ad.id_almacen
                                WHERE m.id_producto = ?
                                ORDER BY m.id_movimiento DESC"); // Cambiado a id_movimiento
        $stmt_movimientos->execute([$producto_id]);
        $movimientos = $stmt_movimientos->fetchAll();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al cargar los detalles del producto: " . $e->getMessage();
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
    <h2>Detalles del Producto</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($producto): ?>
        <div class="product-details">
            <p><strong>N° Inventario:</strong> <?php echo htmlspecialchars($producto['numero_inventario']); ?></p>
            <p><strong>N° Serie:</strong> <?php echo htmlspecialchars($producto['numero_serie'] ?? 'N/A'); ?></p>
            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($producto['descripcion']); ?></p>
            <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($producto['cantidad']); ?></p>
            <p><strong>Almacén Actual:</strong> <?php echo htmlspecialchars($producto['nombre_almacen']); ?></p>
            <p><strong>Tipo de Producto:</strong> <?php echo htmlspecialchars($producto['tipo_producto'] == 'unico' ? 'Único' : 'Genérico'); ?></p>
            <p><strong>Estado:</strong> <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?></p>
            <p><strong>Fecha de Creación:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($producto['fecha_creacion']))); ?></p>
            <p><strong>Último Movimiento:</strong>
                <?php
                // Corrección para evitar el "Undefined index"
                if (isset($producto['fecha_ultimo_movimiento']) && $producto['fecha_ultimo_movimiento']) {
                    echo htmlspecialchars(date('d/m/Y H:i', strtotime($producto['fecha_ultimo_movimiento'])));
                } else {
                    echo 'N/A';
                }
                ?>
            </p>
        </div>

        <h3>Historial de Movimientos</h3>
        <?php if (empty($movimientos)): ?>
            <p>No hay movimientos registrados para este producto.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Movimiento</th> <th>Tipo de Movimiento</th>
                        <th>Almacén Origen</th>
                        <th>Almacén Destino</th>
                        <th>Observaciones</th>
                        <th>Realizado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($movimiento['id_movimiento']); ?></td> <td>
                                <?php
                                    switch ($movimiento['tipo_movimiento']) {
                                        case 'creacion': echo 'Creación'; break;
                                        case 'mover_a': echo 'Movimiento'; break;
                                        case 'retirar': echo 'Retiro'; break;
                                        case 'ingreso': echo 'Ingreso'; break;
                                        default: echo htmlspecialchars($movimiento['tipo_movimiento']); break;
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($movimiento['nombre_almacen_origen'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($movimiento['nombre_almacen_destino'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($movimiento['observaciones'] ?? 'Sin observaciones'); ?></td>
                            <td><?php echo htmlspecialchars($movimiento['nombre_usuario']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="form-actions">
            <a href="editar.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" class="btn btn-warning">Modificar Producto</a>
            <a href="mover.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" class="btn btn-info">Mover/Retirar Producto</a>
            <a href="listar.php" class="btn btn-secondary">Volver al Listado</a>
        </div>

    <?php else: ?>
        <p>Producto no encontrado.</p>
        <a href="listar.php" class="btn btn-secondary">Volver al Listado</a>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>