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

        if (!$producto) {
            $_SESSION['error_message'] = "Producto no encontrado.";
            header("Location: listar.php");
            exit();
        }

        $stmt_almacenes = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre");
        $almacenes = $stmt_almacenes->fetchAll();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al cargar el producto o almacenes: " . $e->getMessage();
        header("Location: listar.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "ID de producto no especificado para el movimiento.";
    header("Location: listar.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $producto) {
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $id_almacen_destino = $_POST['id_almacen_destino'] ?? null;
    $cantidad_movimiento = intval($_POST['cantidad_movimiento'] ?? 1);
    $observaciones = trim($_POST['observaciones'] ?? '');

    $id_almacen_origen = $producto['id_almacen_actual'];

    // Validaciones básicas
    if (empty($tipo_movimiento)) {
        $_SESSION['error_message'] = "Por favor, selecciona un tipo de movimiento.";
    } elseif ($tipo_movimiento === 'mover_a' && (empty($id_almacen_destino) || $id_almacen_destino == $id_almacen_origen)) {
        $_SESSION['error_message'] = "Para mover, debes seleccionar un almacén de destino diferente al actual.";
    } elseif (($tipo_movimiento === 'mover_a' || $tipo_movimiento === 'retirar') && $producto['tipo_producto'] === 'unico' && $cantidad_movimiento != 1) {
        $_SESSION['error_message'] = "Los productos únicos solo se pueden mover o retirar de a uno.";
    } elseif (($tipo_movimiento === 'mover_a' || $tipo_movimiento === 'retirar') && $cantidad_movimiento > $producto['cantidad']) {
        $_SESSION['error_message'] = "No puedes mover o retirar más cantidad de la que hay en stock (" . $producto['cantidad'] . ").";
    } elseif ($cantidad_movimiento <= 0) {
        $_SESSION['error_message'] = "La cantidad a mover/ingresar/retirar debe ser mayor que cero.";
    }
    else {
        try {
            $pdo->beginTransaction();

            $success = false;
            $new_quantity = $producto['cantidad'];

            switch ($tipo_movimiento) {
                case 'mover_a':
                    if ($producto['tipo_producto'] === 'unico') {
                        // Mover producto único: Cambiar su almacén actual
                        $stmt_update_producto = $pdo->prepare("UPDATE productos SET id_almacen_actual = ?, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                        $stmt_update_producto->execute([$id_almacen_destino, $producto_id]);
                    } else {
                        // Mover producto genérico: Ajustar cantidades en origen y destino
                        $new_quantity -= $cantidad_movimiento;
                        $stmt_update_origen = $pdo->prepare("UPDATE productos SET cantidad = ?, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                        $stmt_update_origen->execute([$new_quantity, $producto_id]);

                        // Intentar encontrar el producto genérico en el almacén de destino
                        $stmt_find_in_destination = $pdo->prepare("SELECT id_producto, cantidad FROM productos WHERE numero_inventario = ? AND tipo_producto = 'generico' AND id_almacen_actual = ?");
                        $stmt_find_in_destination->execute([$producto['numero_inventario'], $id_almacen_destino]);
                        $product_in_destination = $stmt_find_in_destination->fetch();

                        if ($product_in_destination) {
                            // Si ya existe, actualizar cantidad
                            $new_quantity_destination = $product_in_destination['cantidad'] + $cantidad_movimiento;
                            $stmt_update_destination = $pdo->prepare("UPDATE productos SET cantidad = ?, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                            $stmt_update_destination->execute([$new_quantity_destination, $product_in_destination['id_producto']]);
                        } else {
                            // Si no existe, crear un nuevo registro de producto genérico en el destino
                            $stmt_insert_destination = $pdo->prepare("INSERT INTO productos (numero_inventario, descripcion, cantidad, tipo_producto, id_almacen_actual, fecha_ultimo_movimiento, fecha_creacion, activo) VALUES (?, ?, ?, 'generico', ?, NOW(), NOW(), TRUE)");
                            $stmt_insert_destination->execute([$producto['numero_inventario'], $producto['descripcion'], $cantidad_movimiento, $id_almacen_destino]);
                        }
                    }
                    $success = true;
                    break;

                case 'retirar':
                    if ($producto['tipo_producto'] === 'unico') {
                        // Retirar producto único: Cambiar a inactivo
                        $stmt_retirar_unico = $pdo->prepare("UPDATE productos SET activo = FALSE, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                        $stmt_retirar_unico->execute([$producto_id]);
                    } else {
                        // Retirar producto genérico: Reducir cantidad
                        $new_quantity -= $cantidad_movimiento;
                        if ($new_quantity < 0) $new_quantity = 0; // Evitar cantidades negativas
                        $stmt_retirar_generico = $pdo->prepare("UPDATE productos SET cantidad = ?, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                        $stmt_retirar_generico->execute([$new_quantity, $producto_id]);
                    }
                    $success = true;
                    break;

                case 'ingreso':
                    // Ingreso de producto genérico: Aumentar cantidad
                    if ($producto['tipo_producto'] === 'unico') {
                        // Un producto único no debería tener "ingresos" de cantidad, solo un cambio de estado si fue retirado.
                        // Si se quiere "ingresar" un producto único que estaba inactivo, se le cambia el estado a activo.
                        if (!$producto['activo']) {
                             $stmt_ingreso_unico = $pdo->prepare("UPDATE productos SET activo = TRUE, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                             $stmt_ingreso_unico->execute([$producto_id]);
                             $success = true;
                        } else {
                            $_SESSION['error_message'] = "Este producto único ya está activo. Si desea cambiar su ubicación, use 'Mover a otro almacén'.";
                            $pdo->rollBack();
                            header("Location: mover.php?id=" . $producto_id);
                            exit();
                        }
                    } else {
                        $new_quantity += $cantidad_movimiento;
                        $stmt_ingreso_generico = $pdo->prepare("UPDATE productos SET cantidad = ?, fecha_ultimo_movimiento = NOW() WHERE id_producto = ?");
                        $stmt_ingreso_generico->execute([$new_quantity, $producto_id]);
                        $success = true;
                    }
                    break;

                default:
                    $_SESSION['error_message'] = "Tipo de movimiento no válido.";
                    break;
            }

            if ($success) {
                // Registrar el movimiento
                $sql_insert_movimiento = "INSERT INTO movimientos (id_producto, tipo_movimiento, id_almacen_origen, id_almacen_destino, observaciones, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_movimiento = $pdo->prepare($sql_insert_movimiento);
                $stmt_movimiento->execute([
                    $producto_id,
                    $tipo_movimiento,
                    ($tipo_movimiento == 'ingreso' && $producto['tipo_producto'] == 'unico' ? null : $id_almacen_origen), // Origen nulo para ingreso de único (re-activación)
                    ($tipo_movimiento == 'retirar' ? null : ($tipo_movimiento == 'ingreso' ? $id_almacen_origen : $id_almacen_destino)), // Destino nulo para retiros, origen actual para ingresos
                    $observaciones . ($producto['tipo_producto'] === 'generico' || ($tipo_movimiento === 'mover_a' && $producto['tipo_producto'] === 'unico') ? " (Cantidad: " . $cantidad_movimiento . ")" : ""), // Añadir cantidad solo si aplica
                    $_SESSION['user_id']
                ]);

                $pdo->commit();
                $_SESSION['success_message'] = "Movimiento de producto realizado con éxito.";
                header("Location: ver.php?id=" . $producto_id);
                exit();
            } else {
                $pdo->rollBack();
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error al realizar el movimiento: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Movimiento de Producto: <?php echo htmlspecialchars($producto['descripcion']); ?></h2>

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
            <p><strong>Cantidad Actual:</strong> <span id="current_quantity"><?php echo htmlspecialchars($producto['cantidad']); ?></span></p>
            <p><strong>Almacén Actual:</strong> <?php echo htmlspecialchars($producto['nombre_almacen']); ?></p>
            <p><strong>Tipo de Producto:</strong> <span id="product_type"><?php echo htmlspecialchars($producto['tipo_producto'] == 'unico' ? 'Único' : 'Genérico'); ?></span></p>
            <p><strong>Estado Actual:</strong> <span id="product_status"><?php echo htmlspecialchars($producto['activo'] ? 'Activo' : 'Inactivo'); ?></span></p>
        </div>

        <form action="mover.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" method="POST">
            <div class="form-group">
                <label for="tipo_movimiento">Tipo de Movimiento: <span style="color: red;">*</span></label>
                <select id="tipo_movimiento" name="tipo_movimiento" class="form-control" required>
                    <option value="">Selecciona un tipo</option>
                    <option value="mover_a">Mover a otro almacén</option>
                    <?php if ($producto['tipo_producto'] === 'generico'): ?>
                        <option value="ingreso">Ingresar más cantidad</option>
                    <?php elseif ($producto['tipo_producto'] === 'unico' && !$producto['activo']): ?>
                        <option value="ingreso">Re-activar (Ingresar)</option>
                    <?php endif; ?>
                    <option value="retirar">Retirar de stock</option>
                </select>
            </div>

            <div class="form-group" id="almacen_destino_group" style="display: none;">
                <label for="id_almacen_destino">Almacén de Destino:</label>
                <select id="id_almacen_destino" name="id_almacen_destino" class="form-control">
                    <option value="">Selecciona un almacén</option>
                    <?php foreach ($almacenes as $almacen): ?>
                        <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>"
                            <?php echo ($almacen['id_almacen'] == $producto['id_almacen_actual']) ? 'disabled' : ''; ?>>
                            <?php echo htmlspecialchars($almacen['nombre']); ?>
                            <?php echo ($almacen['id_almacen'] == $producto['id_almacen_actual']) ? ' (Almacén Actual)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="cantidad_movimiento_group" style="display: none;">
                <label for="cantidad_movimiento">Cantidad:</label>
                <input type="number" id="cantidad_movimiento" name="cantidad_movimiento" class="form-control" value="1" min="1">
                <small id="cantidad_help"></small>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Realizar Movimiento</button>
            <a href="ver.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php else: ?>
        <p>Producto no encontrado o ID no especificado.</p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoMovimientoSelect = document.getElementById('tipo_movimiento');
        const almacenDestinoGroup = document.getElementById('almacen_destino_group');
        const idAlmacenDestinoSelect = document.getElementById('id_almacen_destino');
        const cantidadMovimientoGroup = document.getElementById('cantidad_movimiento_group');
        const cantidadMovimientoInput = document.getElementById('cantidad_movimiento');
        const cantidadHelp = document.getElementById('cantidad_help');
        const productType = document.getElementById('product_type').textContent; // 'Único' o 'Genérico'
        const productStatus = document.getElementById('product_status').textContent; // 'Activo' o 'Inactivo'
        const currentQuantity = parseInt(document.getElementById('current_quantity').textContent);

        function updateFormVisibility() {
            const selectedType = tipoMovimientoSelect.value;

            // Restablecer valores y estados por defecto
            idAlmacenDestinoSelect.value = '';
            idAlmacenDestinoSelect.removeAttribute('required');
            cantidadMovimientoInput.value = 1;
            cantidadMovimientoInput.removeAttribute('max');
            cantidadMovimientoInput.removeAttribute('min');
            cantidadMovimientoInput.disabled = false; // Habilitar por defecto
            cantidadHelp.textContent = '';
            almacenDestinoGroup.style.display = 'none';
            cantidadMovimientoGroup.style.display = 'none';


            if (selectedType === 'mover_a') {
                almacenDestinoGroup.style.display = 'block';
                idAlmacenDestinoSelect.setAttribute('required', 'required');
                cantidadMovimientoGroup.style.display = 'block';

                if (productType === 'Único') {
                    cantidadMovimientoInput.value = 1;
                    cantidadMovimientoInput.disabled = true;
                    cantidadHelp.textContent = 'Los productos únicos se mueven de a uno.';
                } else { // Genérico
                    cantidadMovimientoInput.setAttribute('min', '1');
                    cantidadMovimientoInput.setAttribute('max', currentQuantity);
                    cantidadHelp.textContent = 'Cantidad disponible: ' + currentQuantity;
                }
            } else if (selectedType === 'retirar') {
                cantidadMovimientoGroup.style.display = 'block';

                if (productType === 'Único') {
                    cantidadMovimientoInput.value = 1;
                    cantidadMovimientoInput.disabled = true;
                    cantidadHelp.textContent = 'Los productos únicos se retiran de a uno.';
                } else { // Genérico
                    cantidadMovimientoInput.setAttribute('min', '1');
                    cantidadMovimientoInput.setAttribute('max', currentQuantity);
                    cantidadHelp.textContent = 'Cantidad disponible: ' + currentQuantity;
                }
            } else if (selectedType === 'ingreso') {
                cantidadMovimientoGroup.style.display = 'block';

                if (productType === 'Único') {
                    cantidadMovimientoInput.value = 1; // Aunque es un checkbox, se usa 1 como referencia
                    cantidadMovimientoInput.disabled = true;
                    cantidadHelp.textContent = 'Para re-activar un producto único, su estado cambiará a "Activo".';
                } else { // Genérico
                    cantidadMovimientoInput.setAttribute('min', '1');
                    cantidadHelp.textContent = 'Cantidad a ingresar.';
                }
            }
        }

        tipoMovimientoSelect.addEventListener('change', updateFormVisibility);

        // Inicializar la visibilidad del formulario al cargar la página
        updateFormVisibility();
    });
</script>

<?php
require_once '../../includes/footer.php';
?>