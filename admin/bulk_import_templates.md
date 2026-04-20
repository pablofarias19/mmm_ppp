# Prompts para importación masiva en Mapita

Usá estos prompts para generar archivos JSON con una IA (ChatGPT, Gemini, Claude, etc.).
El archivo generado se sube desde el panel Admin → pestaña Negocios o Marcas → sección "Importar en masa".

---

## PROMPT PARA NEGOCIOS

```
Generá un array JSON de negocios para la plataforma Mapita.
El resultado debe ser un array JSON válido con uno o varios objetos.

CAMPOS OBLIGATORIOS:
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

## PROMPT PARA MARCAS

```
Generá un array JSON de marcas para la plataforma Mapita.
El resultado debe ser un array JSON válido con uno o varios objetos.

CAMPOS OBLIGATORIOS:
- "nombre": nombre de la marca (string, máx. 255 chars)
- "rubro": rubro o industria de la marca (string, ej: "Indumentaria", "Alimentos", "Tecnología")

CAMPOS OPCIONALES:
- "website": URL completa (string)
- "ubicacion": ciudad o país de operación (string, default "Argentina")
- "lat": latitud decimal (número)
- "lng": longitud decimal (número)
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
- "inpi_registrada": está registrada en INPI Argentina (0 o 1)
- "inpi_numero": número de registro INPI (string)
- "inpi_fecha_registro": fecha de registro INPI (string "YYYY-MM-DD")
- "inpi_vencimiento": fecha de vencimiento INPI (string "YYYY-MM-DD")
- "inpi_clases_registradas": clases NIZA registradas (string, ej: "25,35")
- "inpi_tipo": tipo de marca INPI (string, ej: "Nominativa", "Mixta")
- "es_franquicia": opera como franquicia (0 o 1)
- "tiene_zona": tiene zona de exclusividad (0 o 1)
- "zona_radius_km": radio de zona en km (número entero)
- "tiene_licencia": tiene modelo de licencia (0 o 1)
- "estado": "Activa" o "Inactiva"

EJEMPLO DE SALIDA:
[
  {
    "nombre": "Café Martínez",
    "rubro": "Gastronomía",
    "website": "https://www.cafemartinez.com",
    "ubicacion": "Argentina",
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
    "inpi_clases_registradas": "30,43",
    "es_franquicia": 1,
    "tiene_zona": 1,
    "zona_radius_km": 5,
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
5. El campo `lat`/`lng` es opcional para marcas; para negocios es obligatorio.
6. Los tipos de negocio deben coincidir exactamente con la lista indicada en el prompt.
