<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
// Opcional: restringir acceso solo a administradores o al propio usuario
// if ($_SESSION['user_type'] !== 'admin' && $_GET['id'] != $_SESSION['user_id']) {
//     $_SESSION['error_message'] = "No tienes permisos para modificar este usuario.";
//     header("Location: /stoc_gemini/index.php");
//     exit();
// }

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$user_id = $_GET['id'] ?? null;
$user_data = null;

if ($user_id) {
    try {
        $stmt_user = $pdo->prepare("SELECT id_usuario, nombre_usuario, tipo_usuario FROM usuarios WHERE id_usuario = ?");
        $stmt_user->execute([$user_id]);
        $user_data = $stmt_user->fetch();

        if (!$user_data) {
            $_SESSION['error_message'] = "Usuario no encontrado.";
            header("Location: /stock_gemini/index.php"); // O a una lista de usuarios
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error al cargar el usuario: " . $e->getMessage();
        header("Location: /stock_gemini/index.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "ID de usuario no especificado para modificar.";
    header("Location: /stock_gemini/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data) {
    $username = trim($_POST['username'] ?? '');
    $user_type = $_POST['user_type'] ?? 'normal';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (empty($username)) {
        $_SESSION['error_message'] = "El nombre de usuario es obligatorio.";
    } else {
        try {
            // Verificar si el nombre de usuario ya existe para otro usuario
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ? AND id_usuario != ?");
            $stmt_check->execute([$username, $user_id]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['error_message'] = "El nombre de usuario ya está en uso por otro usuario.";
                header("Location: modificar.php?id=" . $user_id);
                exit();
            }

            $sql_update = "UPDATE usuarios SET nombre_usuario = ?, tipo_usuario = ? WHERE id_usuario = ?";
            $params_update = [$username, $user_type, $user_id];

            // Actualizar contraseña si se proporcionó una nueva
            if (!empty($new_password)) {
                if ($new_password !== $confirm_new_password) {
                    $_SESSION['error_message'] = "Las nuevas contraseñas no coinciden.";
                    header("Location: modificar.php?id=" . $user_id);
                    exit();
                } elseif (strlen($new_password) < 6) {
                    $_SESSION['error_message'] = "La nueva contraseña debe tener al menos 6 caracteres.";
                    header("Location: modificar.php?id=" . $user_id);
                    exit();
                }
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $sql_update = "UPDATE usuarios SET nombre_usuario = ?, clave = ?, tipo_usuario = ? WHERE id_usuario = ?";
                $params_update = [$username, $hashed_password, $user_type, $user_id];
            }

            $stmt = $pdo->prepare($sql_update);
            $stmt->execute($params_update);

            $_SESSION['success_message'] = "Usuario '{$username}' actualizado con éxito.";
            header("Location: modificar.php?id=" . $user_id);
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error al actualizar el usuario: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Modificar Usuario: <?php echo htmlspecialchars($user_data['nombre_usuario']); ?></h2>

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

    <?php if ($user_data): ?>
        <form action="modificar.php?id=<?php echo htmlspecialchars($user_data['id_usuario']); ?>" method="POST">
            <div class="form-group">
                <label for="username">Nombre de Usuario: <span style="color: red;">*</span></label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user_data['nombre_usuario']); ?>" required>
            </div>
            <div class="form-group">
                <label for="user_type">Tipo de Usuario:</label>
                <select id="user_type" name="user_type" class="form-control">
                    <option value="normal" <?php echo ($user_data['tipo_usuario'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                    <option value="admin" <?php echo ($user_data['tipo_usuario'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select>
            </div>
            <h3>Cambiar Contraseña (opcional)</h3>
            <div class="form-group">
                <label for="new_password">Nueva Contraseña:</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirmar Nueva Contraseña:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="/stock_gemini/index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php else: ?>
        <p>Usuario no encontrado o ID no especificado.</p>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>