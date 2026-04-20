<?php
// views/brand/monetization_form.php
// Formulario de monetización
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Monetización</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 520px; margin: 0 auto; padding: 30px 0;">
        <h1>Monetización</h1>
        <form method="post" action="">
            <label>Fuentes de ingresos:
                <textarea name="fuentes_ingresos" rows="2"><?= isset($monet) ? htmlspecialchars($monet['fuentes_ingresos']) : '' ?></textarea>
            </label>
            <label>Escalabilidad:
                <input type="text" name="escalabilidad" value="<?= isset($monet) ? htmlspecialchars($monet['escalabilidad']) : '' ?>">
            </label>
            <label>Margen potencial:
                <input type="text" name="margen_potencial" value="<?= isset($monet) ? htmlspecialchars($monet['margen_potencial']) : '' ?>">
            </label>
            <label>Valor como activo intangible:
                <textarea name="valor_activo" rows="2"><?= isset($monet) ? htmlspecialchars($monet['valor_activo']) : '' ?></textarea>
            </label>
            <button type="submit">Guardar monetización</button>
            <a href="brand_detail.php?id=<?= $_GET['id'] ?>" style="margin-left:12px; color:#e53e3e;">Cancelar</a>
        </form>
    </div>
</body>
</html>
