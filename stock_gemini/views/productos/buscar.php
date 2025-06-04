<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$productos = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar'])) {
    $search_performed = true;
    $criterio = trim($_GET['criterio'] ?? '');
    $valor = trim($_GET['valor'] ?? '');

    if (empty($criterio) || empty($valor)) {
        $_SESSION['error_message'] = "Por favor, selecciona un criterio y escribe un valor para buscar.";
    } else {
        $sql = "SELECT p.*, a.nombre as nombre_almacen FROM productos p JOIN almacenes a ON p.id_almacen_actual = a.id_almacen WHERE ";
        $params = ['%' . $valor . '%'];

        switch ($criterio) {
            case 'numero_inventario':
                $sql .= "p.numero_inventario LIKE ?";
                break;
            case 'numero_serie':
                $sql .= "p.numero_serie LIKE ?";
                break;
            case 'descripcion':
                $sql .= "p.descripcion LIKE ?";
                break;
            case 'almacen':
                $sql .= "a.nombre LIKE ?"; // Buscar por nombre de almacén
                break;
            default:
                $_SESSION['error_message'] = "Criterio de búsqueda inválido.";
                $search_performed = false;
                break;
        }

        if ($search_performed) {
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $productos = $stmt->fetchAll();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error al realizar la búsqueda: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <h2>Búsqueda de Producto</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <form action="buscar.php" method="GET">
        <div class="form-group">
            <label for="criterio">Buscar por:</label>
            <select id="criterio" name="criterio" class="form-control" required>
                <option value="">Selecciona un criterio</option>
                <option value="numero_inventario" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'numero_inventario') ? 'selected' : ''; ?>>N° Inventario</option>
                <option value="numero_serie" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'numero_serie') ? 'selected' : ''; ?>>N° Serie</option>
                <option value="descripcion" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'descripcion') ? 'selected' : ''; ?>>Descripción</option>
                <option value="almacen" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'almacen') ? 'selected' : ''; ?>>Almacén</option>
            </select>
        </div>
        <div class="form-group">
            <label for="valor">Valor de Búsqueda:</label>
            <input type="text" id="valor" name="valor" class="form-control" value="<?php echo htmlspecialchars($_GET['valor'] ?? ''); ?>" required>
        </div>
        <button type="submit" name="buscar" value="1" class="btn btn-primary">Buscar</button>
    </form>

    <?php if ($search_performed): ?>
        <h3>Resultados:</h3>
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
                            <td><?php htmlspecialchars($producto['nombre_almacen']); ?></td>
                            <td><?php echo htmlspecialchars($producto['tipo_producto'] == 'unico' ? 'Único' : 'Genérico'); ?></td>
                            <td><?php echo htmlspecialchars($producto['fecha_ultimo_movimiento']); ?></td>
                            <td class="actions">
                                <a href="detalle.php?id=<?php echo $producto['id_producto']; ?>">Ver</a>
                                <a href="modificar.php?id=<?php echo $producto['id_producto']; ?>">Modificar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron productos con los criterios de búsqueda.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>