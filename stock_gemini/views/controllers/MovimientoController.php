<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$stmt = $db->query("SELECT * FROM warehouses ORDER BY name");
$warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Listado de Almacenes</h2>
<form method="get" action="warehouses.php">
    <input type="text" name="q" placeholder="Buscar almacén..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button type="submit">Buscar</button>
</form>


<table class="warehouse-table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Principal</th>
            <th>Subalmacén de</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($warehouses as $warehouse): ?>
        <tr>
            <td><?= htmlspecialchars($warehouse['name']) ?></td>
            <td><?= htmlspecialchars($warehouse['description']) ?></td>
            <td><?= $warehouse['is_main'] ? 'Sí' : 'No' ?></td>
            <td><?= $warehouse['parent_id'] ?: '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>
