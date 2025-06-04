<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/header.php';

$error_message = '';
$success_message = '';
$almacenes = [];

try {
    $stmt = $pdo->query("SELECT id_almacen, nombre, tipo FROM almacenes");
    $almacenes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al cargar los almacenes: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $almacen_id = $_POST['almacen_id'] ?? null;

    if (!$almacen_id) {
        $error_message = "Por favor, selecciona un almacén.";
    } else {
        try {
            $pdo->query("UPDATE almacenes SET es_principal_dashboard = FALSE");
            $stmt = $pdo->prepare("UPDATE almacenes SET es_principal_dashboard = TRUE WHERE id_almacen = ?");
            $stmt->execute([$almacen_id]);
            $success_message = "Almacén principal actualizado correctamente.";
        } catch (PDOException $e) {
            $error_message = "Error al actualizar el almacén principal: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Configurar Almacén Principal</h2>
    <hr>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="almacen_id">Selecciona el almacén principal:</label>
        <select name="almacen_id" id="almacen_id" required>
            <option value="">-- Seleccionar --</option>
            <?php foreach ($almacenes as $almacen): ?>
                <option value="<?php echo $almacen['id_almacen']; ?>">
                    <?php echo htmlspecialchars($almacen['nombre']); ?>
                    <?php echo isset($almacen['tipo']) ? ' (' . htmlspecialchars(ucfirst($almacen['tipo'])) . ')' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <button type="submit">Guardar</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
