<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$almacenes = [];
try {
    $stmt = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre");
    $almacenes = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar los almacenes: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_inventario = trim($_POST['numero_inventario'] ?? '');
    $numero_serie = trim($_POST['numero_serie'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $id_almacen = intval($_POST['id_almacen'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    $tipo_producto = 'unico';
    if ($numero_inventario === 'J000000') {
        $tipo_producto = 'generico';
        $numero_serie = null; // Un producto genérico no tiene número de serie
    }

    if (empty($numero_inventario) || empty($descripcion) || $id_almacen <= 0) {
        $_SESSION['error_message'] = "Por favor, completa todos los campos obligatorios (N° Inventario, Descripción, Almacén).";
    } elseif ($tipo_producto === 'unico' && !preg_match('/^J\d{7}$/', $numero_inventario)) {
        $_SESSION['error_message'] = "El número de inventario para productos únicos debe comenzar con 'J' seguido de 6 dígitos numéricos (ej. J000123).";
    } elseif ($tipo_producto === 'generico' && $cantidad <= 0) {
        $_SESSION['error_message'] = "La cantidad para productos genéricos debe ser mayor que cero.";
    } else {
        try {
            $pdo->beginTransaction();

            // Verificar si el numero de inventario o serie ya existe para productos únicos
            if ($tipo_producto === 'unico') {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE numero_inventario = ? OR (numero_serie IS NOT NULL AND numero_serie = ?)");
                $stmt_check->execute([$numero_inventario, $numero_serie]);
                if ($stmt_check->fetchColumn() > 0) {
                    $_SESSION['error_message'] = "Ya existe un producto único con este N° Inventario o N° Serie.";
                    $pdo->rollBack();
                    header("Location: nuevo.php");
                    exit();
                }
            }

            $sql_insert_producto = "INSERT INTO productos (numero_inventario, numero_serie, descripcion, cantidad, tipo_producto, id_almacen_actual) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_producto = $pdo->prepare($sql_insert_producto);
            $stmt_producto->execute([
                $numero_inventario,
                $numero_serie,
                $descripcion,
                $tipo_producto === 'unico' ? 1 : $cantidad, // Cantidad siempre 1 para únicos
                $tipo_producto,
                $id_almacen
            ]);

            $new_product_id = $pdo->lastInsertId();

            // Registrar movimiento de alta
            $sql_insert_movimiento = "INSERT INTO movimientos (id_producto, tipo_movimiento, id_almacen_origen, id_almacen_destino, observaciones, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_movimiento = $pdo->prepare($sql_insert_movimiento);
            $stmt_movimiento->execute([
                $new_product_id,
                'alta',
                null, // Origen nulo para alta
                $id_almacen,
                $observaciones,
                $_SESSION['user_id']
            ]);

            $pdo->commit();
            $_SESSION['success_message'] = "Producto '{$descripcion}' (N° Inv: {$numero_inventario}) agregado con éxito.";
            header("Location: listar.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error al agregar el producto: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Nuevo Producto</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <form action="nuevo.php" method="POST">
        <div class="form-group">
            <label for="numero_inventario">N° Inventario: <span style="color: red;">*</span></label>
            <input type="text" id="numero_inventario" name="numero_inventario" class="form-control" required placeholder="Ej: J000123 (para único) o J000000 (para genérico)">
            <small>Si es J000000, se considera Producto Genérico.</small>
        </div>
        <div class="form-group">
            <label for="numero_serie">N° Serie: (Solo para Producto Único)</label>
            <input type="text" id="numero_serie" name="numero_serie" class="form-control" placeholder="Opcional para único">
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción: <span style="color: red;">*</span></label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="cantidad">Cantidad: (Solo para Producto Genérico)</label>
            <input type="number" id="cantidad" name="cantidad" class="form-control" value="1" min="1">
            <small>Será ignorado si el producto es Único (cantidad siempre 1).</small>
        </div>
        <div class="form-group">
            <label for="id_almacen">Almacén: <span style="color: red;">*</span></label>
            <select id="id_almacen" name="id_almacen" class="form-control" required>
                <option value="">Selecciona un almacén</option>
                <?php foreach ($almacenes as $almacen): ?>
                    <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>">
                        <?php echo htmlspecialchars($almacen['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="observaciones">Observaciones (Movimiento de Alta):</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Agregar Producto</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
    // Script para deshabilitar/habilitar cantidad y serie según el tipo de producto
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