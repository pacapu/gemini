<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$almacenes = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar'])) {
    $search_performed = true;
    $criterio = trim($_GET['criterio'] ?? '');
    $valor = trim($_GET['valor'] ?? '');

    if (empty($criterio) || empty($valor)) {
        $_SESSION['error_message'] = "Por favor, selecciona un criterio y escribe un valor para buscar.";
    } else {
        $sql = "SELECT a.*, ap.nombre as nombre_almacen_padre FROM almacenes a LEFT JOIN almacenes ap ON a.id_almacen_padre = ap.id_almacen WHERE ";
        $params = ['%' . $valor . '%'];

        switch ($criterio) {
            case 'nombre':
                $sql .= "a.nombre LIKE ?";
                break;
            case 'descripcion':
                $sql .= "a.descripcion LIKE ?";
                break;
            case 'tipo':
                $sql .= "a.tipo LIKE ?";
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
                $almacenes = $stmt->fetchAll();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error al realizar la búsqueda: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <h2>Búsqueda de Almacén</h2>

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
                <option value="nombre" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'nombre') ? 'selected' : ''; ?>>Nombre</option>
                <option value="descripcion" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'descripcion') ? 'selected' : ''; ?>>Descripción</option>
                <option value="tipo" <?php echo (isset($_GET['criterio']) && $_GET['criterio'] == 'tipo') ? 'selected' : ''; ?>>Tipo</option>
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
        <?php if (!empty($almacenes)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Almacén Padre</th>
                        <th>Principal Dashboard</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($almacenes as $almacen): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($almacen['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($almacen['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($almacen['tipo'])); ?></td>
                            <td><?php echo htmlspecialchars($almacen['nombre_almacen_padre'] ?? 'N/A'); ?></td>
                            <td><?php echo $almacen['es_principal_dashboard'] ? 'Sí' : 'No'; ?></td>
                            <td class="actions">
                                <a href="modificar.php?id=<?php echo $almacen['id_almacen']; ?>">Modificar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron almacenes con los criterios de búsqueda.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>