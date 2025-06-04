<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Opcional: Restringir el acceso a este listado solo a usuarios administradores
if ($_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "No tienes permisos para ver la lista de usuarios.";
    header("Location: ../../index.php"); // Redirigir al dashboard o página principal
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$usuarios = [];
$search_query = $_GET['search'] ?? '';

try {
    $sql = "SELECT id_usuario, nombre_usuario, tipo_usuario FROM usuarios WHERE 1"; // Base de la consulta

    $params = [];

    if (!empty($search_query)) {
        $sql .= " AND (nombre_usuario LIKE ? OR tipo_usuario LIKE ?)";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
    }

    $sql .= " ORDER BY nombre_usuario ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar los usuarios: " . $e->getMessage();
}
?>

<div class="container">
    <h2>Listado de Usuarios</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="action-bar">
        <a href="nuevo.php" class="btn btn-primary">Nuevo Usuario</a>
        <form action="listar.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Buscar por Nombre de Usuario o Tipo" value="<?php echo htmlspecialchars($search_query); ?>" class="form-control-inline">
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if (!empty($search_query)): ?>
                <a href="listar.php" class="btn btn-secondary">Limpiar Búsqueda</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($usuarios)): ?>
        <p>No hay usuarios registrados.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre de Usuario</th>
                    <th>Tipo de Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></td>
                        <td>
                            <a href="modificar.php?id=<?php echo htmlspecialchars($usuario['id_usuario']); ?>">Modificar</a> |
                            <a href="eliminar.php?id=<?php echo htmlspecialchars($usuario['id_usuario']); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción es irreversible.');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>