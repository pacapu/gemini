<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$almacenes_padre = [];
try {
    $stmt = $pdo->query("SELECT id_almacen, nombre, tipo FROM almacenes WHERE tipo IN ('principal', 'sucursal') ORDER BY nombre");
    $almacenes_padre = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar almacenes padre: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // Verificar si el nombre ya existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM almacenes WHERE nombre = ?");
            $stmt_check->execute([$nombre]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Ya existe un almacén con este nombre.";
                header("Location: nuevo.php");
                exit();
            }

            $sql = "INSERT INTO almacenes (nombre, descripcion, tipo, id_almacen_padre) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $descripcion,
                $tipo,
                ($tipo === 'subalmacen' ? $id_almacen_padre : null)
            ]);

            $_SESSION['success_message'] = "Almacén '{$nombre}' agregado con éxito.";
            header("Location: listar.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error al agregar el almacén: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Nuevo Almacén / Sucursal / Subalmacén</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <form action="nuevo.php" method="POST">
        <div class="form-group">
            <label for="nombre">Nombre: <span style="color: red;">*</span></label>
            <input type="text" id="nombre" name="nombre" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="tipo">Tipo: <span style="color: red;">*</span></label>
            <select id="tipo" name="tipo" class="form-control" required>
                <option value="">Selecciona un tipo</option>
                <option value="principal" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'principal') ? 'selected' : ''; ?>>Almacén Principal</option>
                <option value="sucursal" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'sucursal') ? 'selected' : ''; ?>>Sucursal</option>
                <option value="subalmacen" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'subalmacen') ? 'selected' : ''; ?>>Subalmacén</option>
            </select>
        </div>
        <div class="form-group" id="parent_almacen_group" style="display: none;">
            <label for="id_almacen_padre">Almacén Padre: <span style="color: red;">*</span></label>
            <select id="id_almacen_padre" name="id_almacen_padre" class="form-control">
                <option value="">Selecciona un almacén padre</option>
                <?php foreach ($almacenes_padre as $almacen): ?>
                    <option value="<?php echo htmlspecialchars($almacen['id_almacen']); ?>">
                        <?php echo htmlspecialchars($almacen['nombre']); ?> (<?php echo htmlspecialchars(ucfirst($almacen['tipo'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Agregar Almacén</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
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
                idAlmacenPadreSelect.value = ''; // Reset selection
            }
        }

        tipoSelect.addEventListener('change', toggleParentAlmacenField);
        toggleParentAlmacenField(); // Call on page load to set initial state
    });
</script>

<?php
require_once '../../includes/footer.php';
?>