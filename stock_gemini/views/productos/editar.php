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
$error_message = '';
$success_message = '';

try {
    $stmt_almacenes = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre");
    $almacenes = $stmt_almacenes->fetchAll();

    if ($producto_id) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            $_SESSION['error_message'] = "Producto no encontrado.";
            header("Location: listar.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "ID de producto no especificado.";
        header("Location: listar.php");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar datos: " . $e->getMessage();
    header("Location: listar.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $producto) {
    $numero_inventario = trim($_POST['numero_inventario'] ?? '');
    $numero_serie = trim($_POST['numero_serie'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $tipo_producto = $_POST['tipo_producto'] ?? '';
    $id_almacen_actual = $_POST['id_almacen_actual'] ?? null;
    $activo = isset($_POST['activo']) ? 1 : 0; // Checkbox

    // Validaciones
    if (empty($numero_inventario) || empty($descripcion) || empty($tipo_producto) || empty($id_almacen_actual)) {
        $error_message = "Todos los campos obligatorios deben ser completados.";
    } elseif ($tipo_producto === 'unico' && !empty($numero_serie)) {
         // Validar que el número de serie no esté duplicado para productos únicos (excluyendo el propio producto)
        $stmt_check_serie = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE numero_serie = ? AND tipo_producto = 'unico' AND id_producto != ?");
        $stmt_check_serie->execute([$numero_serie, $producto_id]);
        if ($stmt_check_serie->fetchColumn() > 0) {
            $error_message = "El número de serie ya existe para otro producto único.";
        }
    } elseif ($tipo_producto === 'generico' && $cantidad <= 0) {
        $error_message = "La cantidad debe ser mayor que cero para productos genéricos.";
    } elseif ($tipo_producto === 'unico' && $cantidad != 1) {
        $error_message = "La cantidad para productos únicos debe ser 1.";
    }


    if (empty($error_message)) {
        try {
            $sql = "UPDATE productos SET
                        numero_inventario = ?,
                        numero_serie = ?,
                        descripcion = ?,
                        cantidad = ?,
                        tipo_producto = ?,
                        id_almacen_actual = ?,
                        activo = ?,
                        fecha_ultimo_movimiento = NOW()
                    WHERE id_producto = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $numero_inventario,
                ($tipo_producto === 'unico' ? $numero_serie : null), // Nulo para genéricos
                $descripcion,
                ($tipo_producto === 'unico' ? 1 : $cantidad), // Siempre 1 para único
                $tipo_producto,
                $id_almacen_actual,
                $activo,
                $producto_id
            ]);

            $_SESSION['success_message'] = "Producto actualizado con éxito.";
            header("Location: ver.php?id=" . $producto_id);
            exit();

        } catch (PDOException $e) {
            $error_message = "Error al actualizar el producto: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Modificar Producto</h2>

    <?php if (!empty($error_message)): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($producto): ?>
        <form action="editar.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" method="POST">
            <div class="form-group">
                <label for="numero_inventario">N° Inventario: <span style="color: red;">*</span></label>
                <input type="text" id="numero_inventario" name="numero_inventario" class="form-control" value="<?php echo htmlspecialchars($producto['numero_inventario']); ?>" required>
            </div>

            <div class="form-group" id="numero_serie_group">
                <label for="numero_serie">N° Serie:</label>
                <input type="text" id="numero_serie" name="numero_serie" class="form-control" value="<?php echo htmlspecialchars($producto['numero_serie']); ?>">
                <small>Solo para productos de tipo "Único".</small>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción: <span style="color: red;">*</span></label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
            </div>

            <div class="form-group" id="cantidad_group">
                <label for="cantidad">Cantidad: <span style="color: red;">*</span></label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" value="<?php echo htmlspecialchars($producto['cantidad']); ?>" min="1" required>
                <small id="cantidad_help">Para productos "Únicos" la cantidad será siempre 1.</small>
            </div>

            <div class="form-group">
                <label for="tipo_producto">Tipo de Producto: <span style="color: red;">*</span></label>
                <select id="tipo_producto" name="tipo_producto" class="form-control" required>
                    <option value="unico" <?php echo ($producto['tipo_producto'] == 'unico' ? 'selected' : ''); ?>>Único (por N° de Serie)</option>
                    <option value="generico" <?php echo ($producto['tipo_producto'] == 'generico' ? 'selected' : ''); ?>>Genérico (por Cantidad)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="id_almacen_actual">Almacén Actual: <span style="color: red;">*</span></label>
                <select id="id_almacen_actual" name="id_almacen_actual" class="form-control" required>
                    <?php foreach ($almacenes as $almacen): ?>
                        <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>"
                            <?php echo ($almacen['id_almacen'] == $producto['id_almacen_actual']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($almacen['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group form-check">
                <input type="checkbox" id="activo" name="activo" class="form-check-input" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="activo">Producto Activo</label>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="ver.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php else: ?>
        <p>No se pudo cargar el producto para modificar.</p>
        <a href="listar.php" class="btn btn-secondary">Volver al Listado</a>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoProductoSelect = document.getElementById('tipo_producto');
        const numeroSerieGroup = document.getElementById('numero_serie_group');
        const numeroSerieInput = document.getElementById('numero_serie');
        const cantidadGroup = document.getElementById('cantidad_group');
        const cantidadInput = document.getElementById('cantidad');
        const cantidadHelp = document.getElementById('cantidad_help');

        function updateFormFields() {
            const selectedType = tipoProductoSelect.value;

            if (selectedType === 'unico') {
                numeroSerieGroup.style.display = 'block';
                numeroSerieInput.setAttribute('required', 'required');
                cantidadInput.value = 1;
                cantidadInput.setAttribute('readonly', 'readonly'); // Hacerlo de solo lectura
                cantidadHelp.textContent = 'Para productos "Únicos" la cantidad es siempre 1.';
            } else { // generico
                numeroSerieGroup.style.display = 'none';
                numeroSerieInput.removeAttribute('required');
                numeroSerieInput.value = ''; // Limpiar el valor
                cantidadInput.removeAttribute('readonly'); // Quitar solo lectura
                cantidadInput.setAttribute('min', '1');
                cantidadHelp.textContent = 'La cantidad para productos "Genéricos" debe ser mayor que cero.';
            }
        }

        tipoProductoSelect.addEventListener('change', updateFormFields);

        // Llamar al cargar para inicializar el estado correcto
        updateFormFields();
    });
</script>

<?php
require_once '../../includes/footer.php';
?>