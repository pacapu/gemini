<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$almacen_id = $_GET['id'] ?? null;
$almacen_data = null;
$almacenes_padre = [];

if ($almacen_id) {
    try {
        $stmt_almacen = $pdo->prepare("SELECT * FROM almacenes WHERE id_almacen = ?");
        $stmt_almacen->execute([$almacen_id]);
        $almacen_data = $stmt_almacen->fetch();

        if (!$almacen_data) {
            $_SESSION['error_message'] = "Almacén no encontrado.";
            header("Location: listar.php");
            exit();
        }

        // Obtener almacenes principales/sucursales para seleccionar como padre (excluyéndose a sí mismo si fuera el caso)
        $stmt_padre = $pdo->prepare("SELECT id_almacen, nombre, tipo FROM almacenes WHERE tipo IN ('principal', 'sucursal') AND id_almacen != ? ORDER BY nombre");
        $stmt_padre->execute([$almacen_id]);
        $almacenes_padre = $stmt_padre->fetchAll();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al cargar el almacén: " . $e->getMessage();
        header("Location: listar.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "ID de almacén no especificado para modificar.";
    header("Location: listar.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $almacen_data) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $id_almacen_padre = $_POST['id_almacen_padre'] ?? null;

    if (empty($nombre) || empty($tipo)) {
        $_SESSION['error_message'] = "El nombre y el tipo de almacén son obligatorios.";
    } elseif ($tipo === 'subalmacen' && (empty($id_almacen_padre) || !is_numeric($id_almacen_padre))) {
        $_SESSION['error_message'] = "Un subalmacén debe tener un almacén padre seleccionado.";
    } else {
        try {
            // Verificar si el nombre ya existe para otro almacén
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM almacenes WHERE nombre = ? AND id_almacen != ?");
            $stmt_check->execute([$nombre, $almacen_id]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Ya existe otro almacén con este nombre.";
                header("Location: modificar.php?id=" . $almacen_id);
                exit();
            }

            $sql = "UPDATE almacenes SET nombre = ?, descripcion = ?, tipo = ?, id_almacen_padre = ? WHERE id_almacen = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $descripcion,
                $tipo,
                ($tipo === 'subalmacen' ? $id_almacen_padre : null),
                $almacen_id
            ]);

            $_SESSION['success_message'] = "Almacén '{$nombre}' actualizado con éxito.";
            header("Location: listar.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error al actualizar el almacén: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Modificar Almacén</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($almacen_data): ?>
        <form action="modificar.php?id=<?php echo htmlspecialchars($almacen_data['id_almacen']); ?>" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre: <span style="color: red;">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($almacen_data['nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($almacen_data['descripcion']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo: <span style="color: red;">*</span></label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="principal" <?php echo ($almacen_data['tipo'] == 'principal') ? 'selected' : ''; ?>>Almacén Principal</option>
                    <option value="sucursal" <?php echo ($almacen_data['tipo'] == 'sucursal') ? 'selected' : ''; ?>>Sucursal</option>
                    <option value="subalmacen" <?php echo ($almacen_data['tipo'] == 'subalmacen') ? 'selected' : ''; ?>>Subalmacén</option>
                </select>
            </div>
            <div class="form-group" id="parent_almacen_group" style="display: none;">
                <label for="id_almacen_padre">Almacén Padre: <span style="color: red;">*</span></label>
                <select id="id_almacen_padre" name="id_almacen_padre" class="form-control">
                    <option value="">Selecciona un almacén padre</option>
                    <?php foreach ($almacenes_padre as $almacen): ?>
                        <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>"
                            <?php echo ($almacen_data['id_almacen_padre'] == $almacen['id_almacen']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($almacen['nombre']); ?> (<?php echo htmlspecialchars(ucfirst($almacen['tipo'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="listar.php" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php else: ?>
        <p>Almacén no encontrado o ID no especificado.</p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const parentAlmacenGroup = document.getElementById('parent_almacen_group');
        const idAlmacenPadreSelect = document.getElementById('id_almacen_padre');

        function toggleParentAlmacenField() {
            if (tipoSelect.value === 'subalmacen') {
                parentAlmacenGroup.style.display = 'block';
                idAlmacenPadreSelect.setAttribute('required', 'required');
            } else {
                parentAlmacenGroup.style.display = 'none';
                idAlmacenPadreSelect.removeAttribute('required');
                // Si cambia de subalmacen a principal/sucursal, limpia la selección de padre
                if (tipoSelect.value !== 'subalmacen' && idAlmacenPadreSelect.value !== '') {
                    idAlmacenPadreSelect.value = '';
                }
            }
        }

        tipoSelect.addEventListener('change', toggleParentAlmacenField);
        toggleParentAlmacenField(); // Call on page load to set initial state
    });
</script>

<?php
require_once '../../includes/footer.php';
?>