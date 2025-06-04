<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$productos = [];
$search_query = $_GET['search'] ?? '';
$almacen_filter = $_GET['almacen'] ?? '';

try {
    $sql = "SELECT p.*, a.nombre as nombre_almacen
            FROM productos p
            JOIN almacenes a ON p.id_almacen_actual = a.id_almacen
            WHERE p.activo = TRUE"; // Solo mostrar productos activos por defecto

    // AÑADIR ESTA LÍNEA PARA FILTRAR POR CANTIDAD MAYOR A 0
    $sql .= " AND p.cantidad > 0";

    $params = [];

    if (!empty($search_query)) {
        $sql .= " AND (p.numero_inventario LIKE ? OR p.numero_serie LIKE ? OR p.descripcion LIKE ?)";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
    }

    if (!empty($almacen_filter)) {
        $sql .= " AND p.id_almacen_actual = ?";
        $params[] = $almacen_filter;
    }

    $sql .= " ORDER BY p.descripcion ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    // Obtener todos los almacenes para el filtro
    $stmt_almacenes = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre");
    $almacenes = $stmt_almacenes->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar los productos: " . $e->getMessage();
}
?>

<div class="container">
    <h2>Listado de Productos</h2>

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

    <div class="action-bar">
        <a href="crear.php" class="btn btn-primary">Nuevo Producto</a>
        <form action="listar.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Buscar por N° Inventario/Serie/Descripción" value="<?php echo htmlspecialchars($search_query); ?>" class="form-control-inline">
            <select name="almacen" class="form-control-inline">
                <option value="">Todos los Almacenes</option>
                <?php foreach ($almacenes as $alm): ?>
                    <option value="<?php echo htmlspecialchars($alm['id_almacen']); ?>" <?php echo ($almacen_filter == $alm['id_almacen'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($alm['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Buscar/Filtrar</button>
            <?php if (!empty($search_query) || !empty($almacen_filter)): ?>
                <a href="listar.php" class="btn btn-secondary">Limpiar Filtros</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($productos)): ?>
        <p>No hay productos registrados que cumplan los criterios.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>N° Inventario</th>
                    <th>N° Serie</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Almacén</th>
                    <th>Tipo</th>
                    <th>Último Movimiento</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['numero_inventario']); ?></td>
                        <td><?php echo htmlspecialchars($producto['numero_serie'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($producto['nombre_almacen']); ?></td>
                        <td><?php echo htmlspecialchars($producto['tipo_producto'] == 'unico' ? 'Único' : 'Genérico'); ?></td>
                        <td>
                            <?php
                                if (isset($producto['fecha_ultimo_movimiento']) && $producto['fecha_ultimo_movimiento']) {
                                    echo htmlspecialchars(date('d/m/Y H:i', strtotime($producto['fecha_ultimo_movimiento'])));
                                } else {
                                    echo '—';
                                }
                            ?>
                        </td>
                        <td><?php echo $producto['activo'] ? 'Sí' : 'No'; ?></td>
                        <td>
                            <a href="ver.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>">Ver</a> |
                            <a href="editar.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>">Modificar</a> |
                            <a href="mover.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>">Mover/Retirar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>