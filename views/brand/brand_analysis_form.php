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
        <div style="background:#fffbeb; border:1px solid #f6ad55; border-left:4px solid #ed8936; border-radius:6px; padding:14px 18px; margin-bottom:24px; font-size:14px; color:#744210; line-height:1.6;">
            <strong>⚠️ Aviso importante:</strong> La información registrada en este formulario es de carácter orientativo.
            Para un <strong>análisis pormenorizado</strong> de su marca le recomendamos consultar con un profesional especializado.
            El <a href="https://fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer" style="color:#c05621; font-weight:600; text-decoration:underline;">Estudio Farias Ortiz</a>
            ofrece asesoramiento personalizado en todas las áreas del derecho marcario.
        </div>
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
