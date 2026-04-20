<?php
// views/brand/dashboard_brands.php
// Listado de marcas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marcas - Dashboard</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 1100px; margin: 0 auto; padding: 30px 0;">
        <h1 style="display: flex; align-items: center; justify-content: space-between;">
            <span>Gestión de Marcas</span>
            <a href="/brand_form" style="background: #2563eb; color: #fff; padding: 8px 18px; border-radius: 5px; font-size: 15px; font-weight: 500; box-shadow: 0 1px 4px rgba(37,99,235,0.08);">+ Nueva Marca</a>
        </h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Rubro</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($brands as $brand): ?>
                <tr>
                    <td><?= htmlspecialchars($brand['id']) ?></td>
                    <td><?= htmlspecialchars($brand['nombre']) ?></td>
                    <td><?= htmlspecialchars($brand['rubro']) ?></td>
                    <td><?= htmlspecialchars($brand['ubicacion']) ?></td>
                    <td><?= htmlspecialchars($brand['estado']) ?></td>
                    <td class="brand-actions">
                        <a href="/brand_detail?id=<?= $brand['id'] ?>">Ver</a>
                        <a href="/brand_form?id=<?= $brand['id'] ?>">Editar</a>
                        <form method="post" action="?delete=<?= $brand['id'] ?>" style="display:inline;">
                            <button type="submit" onclick="return confirm('¿Eliminar marca?');" style="background:#e53e3e; color:#fff; padding:4px 12px; font-size:13px; border-radius:4px; margin-left:4px;">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
