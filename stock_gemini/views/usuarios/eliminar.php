<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Opcional: Restringir el acceso a este script solo a usuarios administradores
if ($_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "No tienes permisos para eliminar usuarios.";
    header("Location: ../../index.php"); // Redirigir al dashboard o página principal
    exit();
}

require_once '../../includes/db_connection.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    $_SESSION['error_message'] = "ID de usuario no especificado para eliminar.";
    header("Location: listar.php"); // Redirige a la lista de usuarios
    exit();
}

try {
    // Evitar que un administrador se elimine a sí mismo (opcional pero recomendado)
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "No puedes eliminar tu propia cuenta de usuario mientras estás conectado.";
        header("Location: listar.php");
        exit();
    }

    // Preparar y ejecutar la consulta para eliminar el usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$user_id]);

    // Verificar si se eliminó alguna fila
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Usuario eliminado con éxito.";
    } else {
        $_SESSION['error_message'] = "No se encontró el usuario con el ID especificado o ya fue eliminado.";
    }

} catch (PDOException $e) {
    // Manejo de errores de la base de datos
    $_SESSION['error_message'] = "Error al eliminar el usuario: " . $e->getMessage();
}

// Redirigir siempre a la lista de usuarios después de intentar la eliminación
header("Location: listar.php");
exit();
?>