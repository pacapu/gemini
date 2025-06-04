<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/header.php';
require_once '../includes/db_connection.php';

$productos = [];
$total_encontrado = 0;
$search_performed = false;
$query_rapida = $_GET['query_rapida'] ?? '';

// Inicializar variables siempre
$fecha_desde = '';
$fecha_hasta = '';
$query_rapida = '';
$inventario_serie = '';
$descripcion = '';
$id_almacen_busqueda = '';

// Si se envió el formulario por GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['buscar']) || !empty($_GET['query_rapida'] ?? ''))) {
    $search_performed = true;
    $query_rapida = $_GET['query_rapida'] ?? '';
    $inventario_serie = $_GET['inventario_serie'] ?? $query_rapida;
    $descripcion = $_GET['descripcion'] ?? $query_rapida;
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    $id_almacen_busqueda = $_GET['id_almacen'] ?? '';


    $sql = "SELECT p.*, a.nombre as nombre_almacen FROM productos p JOIN almacenes a ON p.id_almacen_actual = a.id_almacen WHERE 1=1";
    $params = [];

    if (!empty($inventario_serie)) {
        $sql .= " AND (p.numero_inventario LIKE ? OR p.numero_serie LIKE ?)";
        $params[] = '%' . $inventario_serie . '%';
        $params[] = '%' . $inventario_serie . '%';
    }
    if (!empty($descripcion)) {
        $sql .= " AND p.descripcion LIKE ?";
        $params[] = '%' . $descripcion . '%';
    }
    if (!empty($fecha_desde)) {
        $sql .= " AND p.fecha_ultimo_movimiento >= ?";
        $params[] = $fecha_desde . " 00:00:00";
    }
    if (!empty($fecha_hasta)) {
        $sql .= " AND p.fecha_ultimo_movimiento <= ?";
        $params[] = $fecha_hasta . " 23:59:59";
    }
    if (!empty($id_almacen_busqueda)) {
        $sql .= " AND p.id_almacen_actual = ?";
        $params[] = $id_almacen_busqueda;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        // Calcular el total si la búsqueda incluye descripción o almacén
        if (!empty($descripcion) || !empty($id_almacen_busqueda)) {
            $sql_count = "SELECT COUNT(*) FROM productos p WHERE 1=1";
            $params_count = [];
            if (!empty($descripcion)) {
                $sql_count .= " AND p.descripcion LIKE ?";
                $params_count[] = '%' . $descripcion . '%';
            }
            if (!empty($id_almacen_busqueda)) {
                $sql_count .= " AND p.id_almacen_actual = ?";
                $params_count[] = $id_almacen_busqueda;
            }
            $stmt_count = $pdo->prepare($sql_count);
            $stmt_count->execute($params_count);
            $total_encontrado = $stmt_count->fetchColumn();
        } else {
            $total_encontrado = count($productos);
        }

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al realizar la búsqueda: " . $e->getMessage();
    }
}

// Obtener todos los almacenes para el select del formulario
$almacenes = [];
try {
    $stmt_almacenes = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre");
    $almacenes = $stmt_almacenes->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar almacenes: " . $e->getMessage();
}
?>

<div class="container">
    <h2>Búsqueda Avanzada de Productos</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <form action="busqueda_avanzada.php" method="GET">
        <div class="form-group">
            <label for="inventario_serie">N° Inventario / N° Serie:</label>
            <input type="text" id="inventario_serie" name="inventario_serie" class="form-control" value="<?php echo htmlspecialchars($query_rapida); ?>">
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <input type="text" id="descripcion" name="descripcion" class="form-control" value="<?php echo htmlspecialchars($query_rapida); ?>">
        </div>
        <div class="form-group">
            <label for="id_almacen">Almacén:</label>
            <select id="id_almacen" name="id_almacen" class="form-control">
                <option value="">Todos los Almacenes</option>
                <?php foreach ($almacenes as $almacen): ?>
                    <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>"
                        <?php echo (isset($id_almacen_busqueda) && $id_almacen_busqueda == $almacen['id_almacen']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($almacen['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fecha_desde">Último Movimiento Desde:</label>
            <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde); ?>">
        </div>
        <div class="form-group">
            <label for="fecha_hasta">Último Movimiento Hasta:</label>
            <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
        </div>
        <button type="submit" name="buscar" value="1" class="btn btn-primary">Buscar</button>
    </form>

    <?php if ($search_performed): ?>
        <h3>Resultados de la Búsqueda:</h3>
        <?php if (!empty($productos)): ?>
            <table>
                <thead>
                    <tr>
                        <th>N° Inventario</th>
                        <th>N° Serie</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Almacén</th>
                        <th>Tipo</th>
                        <th>Último Movimiento</th>
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
                            <td><?php echo htmlspecialchars($producto['tipo_producto']); ?></td>
                            <td><?php echo htmlspecialchars($producto['fecha_ultimo_movimiento']); ?></td>
                            <td class="actions">
                                <a href="productos/detalle.php?id=<?php echo $producto['id_producto']; ?>">Ver Detalles</a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Total de resultados: <?php echo $total_encontrado; ?></strong></p>
        <?php else: ?>
            <p>No se encontraron productos con los criterios de búsqueda.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>