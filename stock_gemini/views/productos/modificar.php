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
$almacenes = [];

if ($producto_id) {
    try {
        $stmt_producto = $pdo->prepare("SELECT p.*, a.nombre as nombre_almacen FROM productos p JOIN almacenes a ON p.id_almacen_actual = a.id_almacen WHERE id_producto = ?");
        $stmt_producto->execute([$producto_id]);
        $producto = $stmt_producto->fetch();

        $stmt_almacenes = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre");
        $almacenes = $stmt_almacenes->fetchAll();

        if (!$producto) {
            $_SESSION['error_message'] = "Producto no encontrado.";
            header("Location: listar.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al cargar el producto: " . $e->getMessage();
        header("Location: listar.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "ID de producto no especificado para modificar.";
    header("Location: listar.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $producto) {
    $numero_inventario = trim($_POST['numero_inventario'] ?? '');
    $numero_serie = trim($_POST['numero_serie'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $id_almacen_nuevo = intval($_POST['id_almacen'] ?? 0);
    $observaciones_movimiento = trim($_POST['observaciones_movimiento'] ?? '');

    $tipo_producto_nuevo = 'unico';
    if ($numero_inventario === 'J000000') {
        $tipo_producto_nuevo = 'generico';
        $numero_serie = null; // Un producto genérico no tiene número de serie
    }

    if (empty($numero_inventario) || empty($descripcion) || $id_almacen_nuevo <= 0) {
        $_SESSION['error_message'] = "Por favor, completa todos los campos obligatorios.";
    } elseif ($tipo_producto_nuevo === 'unico' && !preg_match('/^J\d{7}$/', $numero_inventario)) {
        $_SESSION['error_message'] = "El número de inventario para productos únicos debe comenzar con 'J' seguido de 6 dígitos numéricos (ej. J000123).";
    } elseif ($tipo_producto_nuevo === 'generico' && $cantidad <= 0) {
        $_SESSION['error_message'] = "La cantidad para productos genéricos debe ser mayor que cero.";
    } else {
        try {
            $pdo->beginTransaction();

            // Verificar si el numero de inventario o serie ya existe para otros productos (excluyendo el actual)
            if ($tipo_producto_nuevo === 'unico') {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE (numero_inventario = ? OR (numero_serie IS NOT NULL AND numero_serie = ?)) AND id_producto != ?");
                $stmt_check->execute([$numero_inventario, $numero_serie, $producto_id]);
                if ($stmt_check->fetchColumn() > 0) {
                    $_SESSION['error_message'] = "Ya existe otro producto único con este N° Inventario o N° Serie.";
                    $pdo->rollBack();
                    header("Location: modificar.php?id=" . $producto_id);
                    exit();
                }
            }

            // Actualizar datos del producto
            $sql_update_producto = "UPDATE productos SET numero_inventario = ?, numero_serie = ?, descripcion = ?, cantidad = ?, tipo_producto = ?, id_almacen_actual = ?, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?";
            $stmt_update = $pdo->prepare($sql_update_producto);
            $stmt_update->execute([
                $numero_inventario,
                $numero_serie,
                $descripcion,
                $tipo_producto_nuevo === 'unico' ? 1 : $cantidad,
                $tipo_producto_nuevo,
                $id_almacen_nuevo,
                $producto_id
            ]);

            // Registrar movimiento si el almacén ha cambiado
            if ($producto['id_almacen_actual'] != $id_almacen_nuevo) {
                $tipo_movimiento = 'mover_a'; // Generalmente 'mover a' para cambios de almacén
                // Puedes refinar esto según la lógica de "instalar" o "retirar"
                // if ($producto['id_almacen_actual'] es sucursal y $id_almacen_nuevo es principal) { $tipo_movimiento = 'ingreso'; }
                // if ($producto['id_almacen_actual'] es principal y $id_almacen_nuevo es sucursal) { $tipo_movimiento = 'instalar'; }

                $sql_insert_movimiento = "INSERT INTO movimientos (id_producto, tipo_movimiento, id_almacen_origen, id_almacen_destino, observaciones, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_movimiento = $pdo->prepare($sql_insert_movimiento);
                $stmt_movimiento->execute([
                    $producto_id,
                    $tipo_movimiento,
                    $producto['id_almacen_actual'],
                    $id_almacen_nuevo,
                    $observaciones_movimiento,
                    $_SESSION['user_id']
                ]);
            }

            $pdo->commit();
            $_SESSION['success_message'] = "Producto actualizado con éxito.";
            header("Location: listar.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error al actualizar el producto: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Modificar Producto</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($producto): ?>
        <form action="modificar.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" method="POST">
            <div class="form-group">
                <label for="numero_inventario">N° Inventario: <span style="color: red;">*</span></label>
                <input type="text" id="numero_inventario" name="numero_inventario" class="form-control" value="<?php echo htmlspecialchars($producto['numero_inventario']); ?>" required>
                <small>Si es J000000, se considera Producto Genérico.</small>
            </div>
            <div class="form-group">
                <label for="numero_serie">N° Serie: (Solo para Producto Único)</label>
                <input type="text" id="numero_serie" name="numero_serie" class="form-control" value="<?php echo htmlspecialchars($producto['numero_serie'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción: <span style="color: red;">*</span></label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="cantidad">Cantidad: (Solo para Producto Genérico)</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" value="<?php echo htmlspecialchars($producto['cantidad']); ?>" min="1">
                <small>Será ignorado si el producto es Único (cantidad siempre 1).</small>
            </div>
            <div class="form-group">
                <label for="id_almacen">Almacén Actual: <span style="color: red;">*</span></label>
                <select id="id_almacen" name="id_almacen" class="form-control" required>
                    <option value="">Selecciona un almacén</option>
                    <?php foreach ($almacenes as $almacen): ?>
                        <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>"
                            <?php echo ($almacen['id_almacen'] == $producto['id_almacen_actual']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($almacen['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="observaciones_movimiento">Observaciones (si el almacén cambia):</label>
                <textarea id="observaciones_movimiento" name="observaciones_movimiento" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="listar.php" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php else: ?>
        <p>Producto no encontrado o ID no especificado.</p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const numeroInventarioInput = document.getElementById('numero_inventario');
        const numeroSerieInput = document.getElementById('numero_serie');
        const cantidadInput = document.getElementById('cantidad');

        function updateFormFields() {
            if (numeroInventarioInput.value === 'J000000') {
                // Producto Genérico
                numeroSerieInput.value = '';
                numeroSerieInput.disabled = true;
                cantidadInput.disabled = false;
                cantidadInput.required = true;
                cantidadInput.setAttribute('min', '1');
            } else {
                // Producto Único
                numeroSerieInput.disabled = false;
                cantidadInput.value = '1';
                cantidadInput.disabled = true;
                cantidadInput.removeAttribute('required');
                cantidadInput.removeAttribute('min');
            }
        }

        numeroInventarioInput.addEventListener('input', updateFormFields);
        updateFormFields(); // Ejecutar al cargar la página
    });
</script>

<?php
require_once '../../includes/footer.php';
?>