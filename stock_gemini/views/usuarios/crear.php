<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
// Opcional: restringir acceso solo a administradores
// if ($_SESSION['user_type'] !== 'admin') {
//     $_SESSION['error_message'] = "No tienes permisos para crear usuarios.";
//     header("Location: /gemini_stock/index.php");
//     exit();
// }

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

// Opcional: restringir acceso solo a administradores
// if ($_SESSION['user_type'] !== 'admin') {
//     $_SESSION['error_message'] = "No tienes permisos para crear usuarios.";
//     header("Location: /gemini_stock/index.php");
//     exit();
// }

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'normal';

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $_SESSION['error_message'] = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            // Verificar si el nombre de usuario ya existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?");
            $stmt_check->execute([$username]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['error_message'] = "El nombre de usuario ya está en uso.";
                header("Location: nuevo.php");
                exit();
            }

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios (nombre_usuario, clave, tipo_usuario) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $user_type]);

            $_SESSION['success_message'] = "Usuario '{$username}' creado con éxito.";
            header("Location: nuevo.php"); // Puedes redirigir a una lista de usuarios
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error al crear el usuario: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Nuevo Usuario</h2>

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

    <form action="nuevo.php" method="POST">
        <div class="form-group">
            <label for="username">Nombre de Usuario: <span style="color: red;">*</span></label>
            <input type="text" id="username" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña: <span style="color: red;">*</span></label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña: <span style="color: red;">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="user_type">Tipo de Usuario:</label>
            <select id="user_type" name="user_type" class="form-control">
                <option value="normal" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Crear Usuario</button>
        <a href="/gemini_stock/index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php
require_once '../../includes/footer.php';
?>
?>

<div class="container">
    <h2>Nuevo Usuario</h2>

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

    <form action="nuevo.php" method="POST">
        <div class="form-group">
            <label for="username">Nombre de Usuario: <span style="color: red;">*</span></label>
            <input type="text" id="username" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña: <span style="color: red;">*</span></label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña: <span style="color: red;">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="user_type">Tipo de Usuario:</label>
            <select id="user_type" name="user_type" class="form-control">
                <option value="normal" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Crear Usuario</button>
        <a href="/gemini_stock/index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php
require_once '../../includes/footer.php';
?>