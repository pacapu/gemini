<?php
session_start();
require_once '../includes/db_connection.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ''); // Añadido trim()
    $password = trim($_POST['password'] ?? ''); // Añadido trim()

    if (empty($username) || empty($password)) {
        $error_message = "Por favor, ingresa tu usuario y contraseña.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, clave, tipo_usuario FROM usuarios WHERE nombre_usuario = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // Usar FETCH_ASSOC para nombres de columna

            // ¡¡¡CAMBIO CRÍTICO AQUÍ!!!
            // Usar password_verify() para comparar la contraseña ingresada con el hash de la BD
            if ($user && password_verify($password, $user['clave'])) {
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['username'] = $user['nombre_usuario'];
                $_SESSION['user_type'] = $user['tipo_usuario']; // 'admin' o 'normal'
                header("Location: /stock_gemini/index.php");
                exit();
            } else {
                $error_message = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error_message = "Error de conexión a la base de datos: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Control de Stock</title>
    <link rel="stylesheet" href="/stock_gemini/css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        .login-container {
            background-color: #fff;
            padding: 30px;
            /*border-radius: 8px;*/
            /*box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);*/
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 25px;
            color: #2c3e50;
            border-bottom: none;
            padding-bottom: 0;
        }
        .login-container .form-group {
            text-align: left;
        }
        .login-container .form-control {
            width: calc(100% - 20px); /* Ajusta padding */
        }
        .login-container .btn {
            width: 100%;
            padding: 12px;
            font-size: 1.1em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>
</body>
</html>