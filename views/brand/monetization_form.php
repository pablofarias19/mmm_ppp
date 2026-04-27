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
        <div style="background:#fffbeb; border:1px solid #f6ad55; border-left:4px solid #ed8936; border-radius:6px; padding:14px 18px; margin-bottom:24px; font-size:14px; color:#744210; line-height:1.6;">
            <strong>⚠️ Aviso importante:</strong> La información registrada en este formulario es de carácter orientativo.
            Para un <strong>análisis pormenorizado</strong> de las estrategias de monetización de su marca le recomendamos consultar con un profesional especializado.
            El <a href="https://fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer" style="color:#c05621; font-weight:600; text-decoration:underline;">Estudio Farias Ortiz</a>
            ofrece asesoramiento personalizado en todas las áreas del derecho marcario.
        </div>
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
