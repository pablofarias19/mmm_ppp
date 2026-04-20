<?php
// views/brand/brand_analysis_form.php
// Formulario de análisis marcario
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis Marcario</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 520px; margin: 0 auto; padding: 30px 0;">
        <h1>Análisis Marcario</h1>
        <form method="post" action="">
            <label>Distintividad:
                <select name="distintividad" required>
                    <option value="ALTA" <?= isset($analysis) && $analysis['distintividad'] == 'ALTA' ? 'selected' : '' ?>>Alta</option>
                    <option value="MEDIA" <?= isset($analysis) && $analysis['distintividad'] == 'MEDIA' ? 'selected' : '' ?>>Media</option>
                    <option value="BAJA" <?= isset($analysis) && $analysis['distintividad'] == 'BAJA' ? 'selected' : '' ?>>Baja</option>
                </select>
            </label>
            <label>Riesgo de confusión:
                <textarea name="riesgo_confusion" rows="2"><?= isset($analysis) ? htmlspecialchars($analysis['riesgo_confusion']) : '' ?></textarea>
            </label>
            <label>Conflictos en clases Niza:
                <textarea name="conflictos_clases" rows="2"><?= isset($analysis) ? htmlspecialchars($analysis['conflictos_clases']) : '' ?></textarea>
            </label>
            <label>Nivel de protección alcanzable:
                <input type="text" name="nivel_proteccion" value="<?= isset($analysis) ? htmlspecialchars($analysis['nivel_proteccion']) : '' ?>">
            </label>
            <label>Posibilidad de expansión internacional:
                <textarea name="expansion_internacional" rows="2"><?= isset($analysis) ? htmlspecialchars($analysis['expansion_internacional']) : '' ?></textarea>
            </label>
            <button type="submit">Guardar análisis</button>
            <a href="brand_detail.php?id=<?= $_GET['id'] ?>" style="margin-left:12px; color:#e53e3e;">Cancelar</a>
        </form>
    </div>
</body>
</html>
