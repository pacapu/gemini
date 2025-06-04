<?php
// test_hash.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connection.php'; // Ajusta la ruta si es necesario

// Contraseña que usarás para la prueba (la misma que usaste para crear el usuario)
$test_password_plain = 'admin123'; // ¡ASEGÚRATE QUE ES LA CONTRASEÑA EN TEXTO PLANO CORRECTA!

// El hash que copiaste directamente de la base de datos para ese usuario
$test_hashed_password_from_db = '$2y$10$wro8iWAysKJ2yT0CY4qWs.JI17ojhnUlhWvm11Y6l.VOpBisNuCFq'; // Pega el hash aquí

echo "<h3>Prueba de password_verify()</h3>";
echo "Contraseña plana de prueba: " . htmlspecialchars($test_password_plain) . "<br>";
echo "Hash de la BD: " . htmlspecialchars($test_hashed_password_from_db) . "<br>";

if (password_verify($test_password_plain, $test_hashed_password_from_db)) {
    echo "<p style='color: green; font-weight: bold;'>¡password_verify() FUNCIONA CORRECTAMENTE con este hash y contraseña!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>¡password_verify() FALLA!</p>";
    echo "<p>Posibles causas: </p>";
    echo "<ul>";
    echo "<li>La contraseña plana ('{$test_password_plain}') no coincide con el hash.</li>";
    echo "<li>El hash copiado ('{$test_hashed_password_from_db}') no es el correcto.</li>";
    echo "<li>Problemas de codificación de caracteres.</li>";
    echo "</ul>";
}

echo "<hr>";

// Opcional: Generar un nuevo hash para la misma contraseña plana
$new_hash_for_test_password = password_hash($test_password_plain, PASSWORD_BCRYPT);
echo "Nuevo hash generado para '{$test_password_plain}': " . htmlspecialchars($new_hash_for_test_password) . "<br>";

if (password_verify($test_password_plain, $new_hash_for_test_password)) {
    echo "<p style='color: green;'>Confirmación: password_hash y password_verify son compatibles.</p>";
} else {
    echo "<p style='color: red;'>¡Alerta! password_hash y password_verify NO son compatibles en este entorno.</p>";
}

echo "<hr>";
echo "<h3>Prueba de lectura de la DB para un usuario específico</h3>";

try {
    $stmt_user = $pdo->prepare("SELECT id_usuario, nombre_usuario, clave FROM usuarios WHERE nombre_usuario = ?");
    $stmt_user->execute(['pablo']); // Reemplaza con el nombre de usuario de prueba
    $user_from_db = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user_from_db) {
        echo "<p>Usuario '{$user_from_db['nombre_usuario']}' encontrado en DB.</p>";
        echo "Hash de la DB para este usuario: " . htmlspecialchars($user_from_db['clave']) . "<br>";

        if (password_verify($test_password_plain, $user_from_db['clave'])) {
            echo "<p style='color: green; font-weight: bold;'>¡password_verify() FUNCIONA con el hash directamente de la DB!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>¡password_verify() FALLA con el hash de la DB!</p>";
            echo "<p>Esto sugiere un problema de codificación entre PHP y la DB, o que el hash guardado para '{$user_from_db['nombre_usuario']}' no corresponde a '{$test_password_plain}'.</p>";
        }
    } else {
        echo "<p style='color: red;'>Usuario 'pablo' NO encontrado en la base de datos.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al leer de la base de datos: " . $e->getMessage() . "</p>";
}

?>