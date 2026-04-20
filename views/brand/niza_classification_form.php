<?php
// views/brand/niza_classification_form.php
// Formulario de clasificación Niza
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clasificación Niza</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 520px; margin: 0 auto; padding: 30px 0;">
        <h1>Clasificación Niza</h1>
        <form method="post" action="">
            <label>Clase principal:
                <input type="number" name="clase_principal" min="1" max="45" required value="<?= isset($niza) ? htmlspecialchars($niza['clase_principal']) : '' ?>">
            </label>
            <label>Clases complementarias estratégicas:
                <input type="text" name="clases_complementarias" value="<?= isset($niza) ? htmlspecialchars($niza['clases_complementarias']) : '' ?>">
                <small style="color:#7f8c8d;">Separar por coma (ej: 9,35,41)</small>
            </label>
            <label>Riesgo de colisión en cada clase:
                <textarea name="riesgo_colision" rows="2"><?= isset($niza) ? htmlspecialchars($niza['riesgo_colision']) : '' ?></textarea>
            </label>
            <button type="submit">Guardar clasificación</button>
            <a href="brand_detail.php?id=<?= $_GET['id'] ?>" style="margin-left:12px; color:#e53e3e;">Cancelar</a>
        </form>
    </div>
</body>
</html>
