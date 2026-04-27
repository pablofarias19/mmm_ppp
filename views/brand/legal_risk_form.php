<?php
// views/brand/legal_risk_form.php
// Formulario de riesgo legal
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Riesgo Legal</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 520px; margin: 0 auto; padding: 30px 0;">
        <h1>Riesgo Legal</h1>
        <div style="background:#fffbeb; border:1px solid #f6ad55; border-left:4px solid #ed8936; border-radius:6px; padding:14px 18px; margin-bottom:24px; font-size:14px; color:#744210; line-height:1.6;">
            <strong>⚠️ Aviso importante:</strong> La información registrada en este formulario es de carácter orientativo.
            Para un <strong>análisis pormenorizado</strong> de los riesgos legales de su marca le recomendamos consultar con un profesional especializado.
            El <a href="https://fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer" style="color:#c05621; font-weight:600; text-decoration:underline;">Estudio Farias Ortiz</a>
            ofrece asesoramiento personalizado en todas las áreas del derecho marcario.
        </div>
        <form method="post" action="">
            <label>Riesgo de oposición:
                <textarea name="riesgo_oposicion" rows="2"><?= isset($risk) ? htmlspecialchars($risk['riesgo_oposicion']) : '' ?></textarea>
            </label>
            <label>Riesgo de nulidad:
                <textarea name="riesgo_nulidad" rows="2"><?= isset($risk) ? htmlspecialchars($risk['riesgo_nulidad']) : '' ?></textarea>
            </label>
            <label>Riesgo de infracción:
                <textarea name="riesgo_infraccion" rows="2"><?= isset($risk) ? htmlspecialchars($risk['riesgo_infraccion']) : '' ?></textarea>
            </label>
            <label>Estrategias defensivas:
                <textarea name="estrategias_defensivas" rows="2"><?= isset($risk) ? htmlspecialchars($risk['estrategias_defensivas']) : '' ?></textarea>
            </label>
            <button type="submit">Guardar riesgo legal</button>
            <a href="brand_detail.php?id=<?= $_GET['id'] ?>" style="margin-left:12px; color:#e53e3e;">Cancelar</a>
        </form>
    </div>
</body>
</html>
