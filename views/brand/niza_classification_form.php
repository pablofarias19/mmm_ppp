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
        <div style="background:#fffbeb; border:1px solid #f6ad55; border-left:4px solid #ed8936; border-radius:6px; padding:14px 18px; margin-bottom:24px; font-size:14px; color:#744210; line-height:1.6;">
            <strong>⚠️ Aviso importante:</strong> La información registrada en este formulario es de carácter orientativo.
            Para un <strong>análisis pormenorizado</strong> de la clasificación de su marca le recomendamos consultar con un profesional especializado.
            El <a href="https://fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer" style="color:#c05621; font-weight:600; text-decoration:underline;">Estudio Farias Ortiz</a>
            ofrece asesoramiento personalizado en todas las áreas del derecho marcario.
        </div>
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
