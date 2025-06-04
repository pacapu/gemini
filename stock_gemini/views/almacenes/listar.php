<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/db_connection.php';

$almacenes_principales = [];
$almacenes_sucursales = [];
$subalmacenes_por_padre = [];

try {
    // Almacenes principales y sucursales
    $stmt = $pdo->query("SELECT * FROM almacenes WHERE tipo IN ('principal', 'sucursal') ORDER BY nombre");
    $all_almacenes = $stmt->fetchAll();

    foreach ($all_almacenes as $almacen) {
        if ($almacen['tipo'] == 'principal') {
            $almacenes_principales[] = $almacen;
        } else {
            $almacenes_sucursales[] = $almacen;
        }
    }

    // Subalmacenes
    $stmt_sub = $pdo->query("SELECT * FROM almacenes WHERE tipo = 'subalmacen' ORDER BY id_almacen_padre, nombre");
    $subalmacenes_raw = $stmt_sub->fetchAll();

    foreach ($subalmacenes_raw as $sub) {
        $subalmacenes_por_padre[$sub['id_almacen_padre']][] = $sub;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al cargar los almacenes: " . $e->getMessage();
}
?>

<div class="container">
    <h2>Listado de Almacenes</h2>

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

    <p>
        <a href="nuevo.php" class="btn btn-primary">Nuevo Almacén/Sucursal/Subalmacén</a>
        <a href="configurar_principal.php" class="btn btn-info">Configurar Almacén Principal</a>
    </p>

    <h3>Almacenes Principales</h3>
    <?php if (!empty($almacenes_principales)): ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Almacén Principal Dashboard</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($almacenes_principales as $almacen): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($almacen['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($almacen['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($almacen['tipo'])); ?></td>
                        <td><?php echo $almacen['es_principal_dashboard'] ? 'Sí' : 'No'; ?></td>
                        <td class="actions">
                            <a href="modificar.php?id=<?php echo $almacen['id_almacen']; ?>">Modificar</a>
                            </td>
                    </tr>
                    <?php
                    // Mostrar subalmacenes si existen
                    if (isset($subalmacenes_por_padre[$almacen['id_almacen']])):
                    ?>
                        <tr>
                            <td colspan="5" style="padding-left: 40px; background-color: #f9f9f9;">
                                <strong>Subalmacenes de <?php echo htmlspecialchars($almacen['nombre']); ?>:</strong>
                                <ul>
                                    <?php foreach ($subalmacenes_por_padre[$almacen['id_almacen']] as $subalmacen): ?>
                                        <li>
                                            <?php echo htmlspecialchars($subalmacen['nombre']); ?>: <?php echo htmlspecialchars($subalmacen['descripcion']); ?>
                                            (<a href="modificar.php?id=<?php echo $subalmacen['id_almacen']; ?>">Modificar</a>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay almacenes principales registrados.</p>
    <?php endif; ?>

    <h3>Sucursales</h3>
    <?php if (!empty($almacenes_sucursales)): ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($almacenes_sucursales as $almacen): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($almacen['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($almacen['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($almacen['tipo'])); ?></td>
                        <td class="actions">
                            <a href="modificar.php?id=<?php echo $almacen['id_almacen']; ?>">Modificar</a>
                        </td>
                    </tr>
                    <?php
                    // Mostrar subalmacenes si existen
                    if (isset($subalmacenes_por_padre[$almacen['id_almacen']])):
                    ?>
                        <tr>
                            <td colspan="4" style="padding-left: 40px; background-color: #f9f9f9;">
                                <strong>Subalmacenes de <?php echo htmlspecialchars($almacen['nombre']); ?>:</strong>
                                <ul>
                                    <?php foreach ($subalmacenes_por_padre[$almacen['id_almacen']] as $subalmacen): ?>
                                        <li>
                                            <?php echo htmlspecialchars($subalmacen['nombre']); ?>: <?php echo htmlspecialchars($subalmacen['descripcion']); ?>
                                            (<a href="modificar.php?id=<?php echo $subalmacen['id_almacen']; ?>">Modificar</a>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay sucursales registradas.</p>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php';
?>