<?php
// views/brand/business_model_list.php
// Listado y alta de modelos de negocio para una marca
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modelos de Negocio</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 30px 0;">
        <h1>Modelos de Negocio</h1>
        <form method="post" action="">
            <label>Tipo:
                <select name="tipo" required>
                    <option value="EXPLOTACION_DIRECTA">Explotación directa</option>
                    <option value="LICENCIAMIENTO">Licenciamiento de marca</option>
                    <option value="FRANQUICIA">Franquicia</option>
                    <option value="MARCA_BLANCA">Marca blanca / sublicencias</option>
                    <option value="ACTIVO_DIGITAL">Activo digital</option>
                </select>
            </label>
            <label>Descripción:
                <textarea name="descripcion" rows="2"></textarea>
            </label>
            <button type="submit">Agregar modelo</button>
            <a href="brand_detail.php?id=<?= $_GET['id'] ?>" style="margin-left:12px; color:#e53e3e;">Volver al detalle</a>
        </form>
        <h2 style="margin-top:36px;">Modelos existentes</h2>
        <ul style="list-style:none; padding:0;">
            <?php foreach ($models as $model): ?>
                <li style="background:#fff; border-radius:7px; box-shadow:0 1px 4px rgba(0,0,0,0.04); margin-bottom:12px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between;">
                    <span><strong><?= htmlspecialchars($model['tipo']) ?>:</strong> <?= htmlspecialchars($model['descripcion']) ?></span>
                    <form method="post" action="?id=<?= $_GET['id'] ?>&delete=<?= $model['id'] ?>" style="display:inline; margin:0;">
                        <button type="submit" onclick="return confirm('¿Eliminar modelo?');" style="background:#e53e3e; color:#fff; padding:4px 12px; font-size:13px; border-radius:4px;">Eliminar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
