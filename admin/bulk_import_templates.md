# Guía de importación masiva para Mapita

> **Para IA / generadores de JSON:** Leé esta guía completa antes de generar datos.
> Seguir exactamente las reglas garantiza que el JSON sea importado sin errores por el sistema.

Hay **dos formas** de importar en masa:

| Opción | URL | Descripción |
|--------|-----|-------------|
| Módulo dedicado (recomendado) | `/admin/importar_marcas_negocios.php` | UI completa: valida, geocodifica, muestra reporte y permite confirmar importación. |
| Panel Admin (rápido) | `/admin/` → pestaña Negocios o Marcas | Widget integrado, sin reporte de validación detallado. |

---

## ¿Cómo geocodifica Mapita las marcas y negocios?

Cuando un JSON llega sin `lat`/`lng`, el módulo de importación masiva
(`admin/importar_marcas_negocios.php`) aplica la siguiente lógica **automáticamente**:

1. **Dirección explícita** (`address` para negocios, `ubicacion` para marcas): se envía
   a [Nominatim (OpenStreetMap)](https://nominatim.openstreetmap.org/) para obtener
   latitud y longitud sin costo.
2. **Fallo de geocodificación**: si Nominatim no puede resolver la dirección, se usan
   las coordenadas de fallback de Buenos Aires (`-34.6037`, `-58.3816`).
3. **Sin dirección**: se aplica directamente el fallback de Buenos Aires.

> **Regla de ubicación de la marca:**
> - Primero se usa el domicilio del titular.
> - Si el titular es extranjero, se usa el domicilio declarado en Argentina.
> - Si ambos faltan, se asigna `"Argentina"` como `ubicacion` y las coordenadas de fallback.

**Recomendación para la IA generadora:** incluir siempre `lat` y `lng` precalculados
(obtenidos de cualquier geocodificador) para evitar el fallback y garantizar posicionamiento
exacto en el mapa. Si no se incluyen, el sistema los calculará automáticamente.

---

## ¿Qué valida el módulo?

El módulo `admin/importar_marcas_negocios.php` ejecuta estas validaciones antes de importar:

| Regla | Detalle |
|-------|---------|
| Raíz es array | El JSON debe comenzar con `[` (array), no con `{` (objeto). |
| Campos obligatorios presentes | Ver lista por tipo más abajo. |
| Tipos de datos correctos | Strings donde se pide string, números donde se pide número. |
| Booleanos como 0/1 | Nunca `true`/`false` ni `"yes"`/`"no"`. |
| Fechas INPI | Formato estricto `YYYY-MM-DD`. |
| Estado válido | Solo `"Activa"` o `"Inactiva"` (con mayúscula inicial). |
| Deduplicación de marcas | Si el mismo `nombre` aparece más de una vez, se consolidan automáticamente las clases NIZA. |

---

---

## PROMPT PARA NEGOCIOS (IMPORTACIÓN MASIVA)

```
Generá un array JSON de negocios para la plataforma Mapita.
El resultado debe ser un array JSON válido con uno o varios objetos.
No incluyas texto adicional, comentarios ni bloque markdown: solo JSON puro.

CAMPOS OBLIGATORIOS POR OBJETO:
- "name": nombre del negocio (string, máx. 255 chars)
- "business_type": tipo exacto de la siguiente lista:
  restaurante, cafeteria, bar, panaderia, heladeria, pizzeria,
  supermercado, comercio, autos_venta, motos_venta, indumentaria, ferreteria,
  electronica, muebleria, floristeria, libreria,
  farmacia, hospital, odontologia, veterinaria, optica,
  salon_belleza, barberia, spa, gimnasio,
  banco, inmobiliaria, seguros, abogado, contador, taller, construccion, remate,
  academia, escuela, hotel, turismo, cine, otros
- "address": dirección completa (string)
- "lat": latitud decimal (número, ej: -34.6037)
- "lng": longitud decimal (número, ej: -58.3816)

CAMPOS OPCIONALES:
- "phone": teléfono con código de país (string, ej: "+541112345678")
- "email": correo electrónico (string)
- "website": URL completa (string, ej: "https://www.negocio.com")
- "instagram": usuario de Instagram (string, ej: "@negocio")
- "facebook": usuario o página de Facebook (string)
- "tiktok": usuario de TikTok (string)
- "description": descripción breve (string, máx. 2000 chars)
- "certifications": certificaciones o habilitaciones (string)
- "has_delivery": tiene delivery (0 o 1)
- "has_card_payment": acepta tarjeta (0 o 1)
- "is_franchise": es franquicia (0 o 1)
- "price_range": rango de precio del 1 al 5 (1=económico, 5=caro)
- "company_size": "familiar", "pyme", "grande" o "multinacional"
- "location_city": ciudad (string)
- "style": estilo o concepto del negocio (string)

REGLAS IMPORTANTES:
- El JSON debe ser un array en la raíz: `[ { ... }, { ... } ]`
- "business_type" debe coincidir exactamente con la lista permitida (en minúsculas y sin variantes).
- "lat" y "lng" deben ser números decimales válidos.
- Los campos booleanos (`has_delivery`, `has_card_payment`, `is_franchise`) deben enviarse como `0` o `1`.
- "price_range" se acepta en 1 a 5 (si llega fuera de rango, la API lo ajusta al límite más cercano).

EJEMPLO DE SALIDA:
[
  {
    "name": "Panadería La Tradición",
    "business_type": "panaderia",
    "address": "Av. Rivadavia 3456, CABA, Argentina",
    "lat": -34.6156,
    "lng": -58.4217,
    "phone": "+541145678901",
    "email": "contacto@latradicion.com.ar",
    "website": "https://latradicion.com.ar",
    "description": "Panadería artesanal con más de 30 años de tradición familiar.",
    "has_delivery": 1,
    "has_card_payment": 1,
    "is_franchise": 0,
    "price_range": 2,
    "company_size": "familiar",
    "location_city": "Buenos Aires"
  }
]
```

---

## PROMPT PARA MARCAS (IMPORTACIÓN MASIVA)

```
Generá un array JSON de marcas para la plataforma Mapita.
El resultado debe ser un array JSON válido con uno o varios objetos.
No incluyas texto adicional, comentarios ni bloque markdown: solo JSON puro.

CAMPOS OBLIGATORIOS POR OBJETO:
- "nombre": nombre de la marca (string, máx. 255 chars)
- "rubro": rubro o industria de la marca (string, ej: "Indumentaria", "Alimentos", "Tecnología")
- "inpi_registrada": está registrada en INPI Argentina (ENTERO 0 o 1, nunca true/false)
- "es_franquicia": opera como franquicia (ENTERO 0 o 1, nunca true/false)
- "tiene_zona": tiene zona de exclusividad (ENTERO 0 o 1, nunca true/false)
- "tiene_licencia": tiene modelo de licencia (ENTERO 0 o 1, nunca true/false)
- "estado": SOLO "Activa" o "Inactiva" (con mayúscula inicial, sin variantes)

CAMPOS OPCIONALES:
- "website": URL completa (string)
- "ubicacion": ciudad o país de operación (string, default "Argentina")
- "lat": latitud decimal (número, ej: -34.6037). Si no se proporciona, el sistema geocodifica.
- "lng": longitud decimal (número, ej: -58.3816). Si no se proporciona, el sistema geocodifica.
- "description": descripción corta de la marca (string)
- "extended_description": descripción larga o historia (string)
- "clase_principal": clase NIZA principal (string, ej: "25" para indumentaria)
- "founded_year": año de fundación (número entero, ej: 1998)
- "annual_revenue": facturación anual estimada (string, ej: "1M-5M USD")
- "instagram": usuario de Instagram (string)
- "facebook": página de Facebook (string)
- "tiktok": usuario de TikTok (string)
- "twitter": usuario de Twitter/X (string)
- "linkedin": página de LinkedIn (string)
- "youtube": canal de YouTube (string)
- "whatsapp": número con código de país (string, ej: "+541112345678")
- "historia_marca": historia completa de la marca (string largo)
- "target_audience": descripción del público objetivo (string)
- "propuesta_valor": propuesta de valor diferencial (string)
- "inpi_numero": número de registro INPI (string)
- "inpi_fecha_registro": fecha de registro INPI (string "YYYY-MM-DD")
- "inpi_vencimiento": fecha de vencimiento INPI (string "YYYY-MM-DD")
- "inpi_clases_registradas": clases NIZA registradas separadas por coma (string, ej: "25,35,42")
- "inpi_tipo": tipo de marca INPI (string, ej: "Nominativa", "Mixta")
- "zona_radius_km": radio de zona en km (número entero)

REGLAS CRÍTICAS — INCUMPLIRLAS CAUSARÁ ERRORES DE IMPORTACIÓN:
1. La raíz DEBE ser un array: [ { ... }, { ... } ]. Nunca un objeto raíz.
2. "estado" SOLO puede ser "Activa" o "Inactiva" (con mayúscula exacta).
3. Los booleanos ("inpi_registrada", "es_franquicia", "tiene_zona", "tiene_licencia") DEBEN ser
   el ENTERO 0 o el ENTERO 1. Nunca strings "0"/"1", nunca true/false, nunca "sí"/"no".
4. Las fechas INPI ("inpi_fecha_registro", "inpi_vencimiento") DEBEN tener formato YYYY-MM-DD.
   Ejemplo correcto: "2023-05-20". Incorrecto: "20/05/2023" o "May 20, 2023".
5. DEDUPLICACIÓN: si la misma marca aparece en varias clases NIZA, generar UN SOLO objeto:
   - Conservar la clase principal en "clase_principal".
   - Incluir todas las clases en "inpi_clases_registradas" (ej: "25,35,42").
   - Agregar nota en "extended_description" sobre las clases adicionales.
   - NO repetir objetos con el mismo "nombre" solo por cambiar la clase NIZA.
6. REGLA DE UBICACIÓN:
   - Usar el domicilio del titular como "ubicacion".
   - Si el titular es extranjero, usar su domicilio declarado en Argentina.
   - Si faltan ambos, usar "Argentina" como valor de "ubicacion".
   - Incluir "lat" y "lng" cuando se conozcan (coordenadas del domicilio).
     Si se omiten, el sistema los calculará automáticamente (geocodificación gratuita).

VALIDACIÓN ANTES DE RESPONDER:
- El JSON debe ser parseable sin errores.
- No debe haber duplicados de nombre+denominación.
- "inpi_clases_registradas" debe ser consistente con la regla de consolidación.
- Verificar que todos los tipos de datos sean correctos.

EJEMPLO DE SALIDA:
[
  {
    "nombre": "Café Martínez",
    "rubro": "Gastronomía",
    "website": "https://www.cafemartinez.com",
    "ubicacion": "Av. Corrientes 1234, Buenos Aires, Argentina",
    "lat": -34.6037,
    "lng": -58.3816,
    "description": "Cadena de cafeterías premium con presencia en todo el país.",
    "extended_description": "Café Martínez nació en 1933 y es una de las marcas de café más reconocidas de Argentina.",
    "clase_principal": "30",
    "founded_year": 1933,
    "instagram": "@cafemartinez",
    "facebook": "CafeMartinezArgentina",
    "historia_marca": "Fundada en Buenos Aires en 1933 por la familia Martínez...",
    "target_audience": "Adultos de 25 a 55 años, profesionales y familias de nivel socioeconómico medio-alto.",
    "propuesta_valor": "Café de calidad premium con experiencia de café europeo en Argentina.",
    "inpi_registrada": 1,
    "inpi_numero": "2847561",
    "inpi_fecha_registro": "2005-03-15",
    "inpi_vencimiento": "2025-03-15",
    "inpi_clases_registradas": "30,43",
    "inpi_tipo": "Nominativa",
    "es_franquicia": 1,
    "tiene_zona": 1,
    "zona_radius_km": 5,
    "tiene_licencia": 0,
    "estado": "Activa"
  }
]
```

---

## Notas de uso

1. El archivo JSON debe ser guardado con extensión `.json` y codificación UTF-8.
2. Se puede incluir un negocio o marca por archivo, o varios en el mismo array.
3. El tamaño máximo del archivo es **2MB**.
4. Si algún elemento falla la validación, los demás se importan igualmente y los errores se muestran en pantalla.
5. El campo `lat`/`lng` es **opcional** para ambos tipos. Si se omite, el módulo
   `admin/importar_marcas_negocios.php` geocodifica automáticamente usando la dirección
   (campo `ubicacion` para marcas o `address` para negocios) vía OpenStreetMap Nominatim.
   Si la geocodificación falla, se usan las coordenadas de Buenos Aires como fallback (-34.6037, -58.3816).
6. Los tipos de negocio deben coincidir exactamente con la lista indicada en el prompt.
7. Para marcas, todos los campos booleanos y `estado` son ahora **obligatorios** en el módulo
   `admin/importar_marcas_negocios.php`. El widget rápido del Panel Admin sigue siendo permisivo.

---

## Modo de consumo de datos (Admin + API)

### Módulo dedicado (recomendado) — `admin/importar_marcas_negocios.php`

1. Accedé a `/admin/importar_marcas_negocios.php`.
2. Seleccioná el tipo (Marcas o Negocios) y subí el archivo JSON.
3. El módulo valida, deduplica y geocodifica automáticamente.
4. Se muestra un reporte con: registros válidos, errores por fila, duplicados y estado de geocodificación.
5. Confirmás la importación haciendo clic en el botón de confirmación.

### Widget Panel Admin — `/admin/`

1. Desde **Admin → pestaña Negocios o Marcas**, usá la sección **Importar en masa (JSON)**.
2. El frontend envía `multipart/form-data` a `POST /api/bulk_import.php` con:
   - `file`: archivo JSON
   - `type`: `businesses` o `brands`
3. La API procesa cada elemento del array de forma independiente (importación parcial si hay errores).
4. Respuesta esperada:
   - `success` (bool)
   - `imported` (cantidad importada)
   - `total` (cantidad total en el array)
   - `errors` (lista de errores por fila)
   - `message` (resumen para UI)
5. Si `errors` tiene elementos, se muestran en pantalla y el resto de registros válidos igualmente se guarda.

---

## Diferencias entre módulo dedicado y widget del Panel

| Característica | Módulo dedicado | Widget Panel |
|----------------|-----------------|--------------|
| URL | `/admin/importar_marcas_negocios.php` | `/admin/` |
| Geocodificación automática | ✅ Sí (Nominatim) | ❌ No |
| Validación completa de marcas | ✅ Sí (todos los campos obligatorios) | ⚠️ Mínima |
| Deduplicación NIZA | ✅ Sí | ❌ No |
| Reporte de validación | ✅ Detallado | ❌ Solo resumen |
| Confirmación antes de importar | ✅ Paso de revisión | ❌ Importa directo |
| JSON enriquecido descargable | ✅ Preview disponible | ❌ No |
