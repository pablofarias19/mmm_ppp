# Sistema de Internacionalización (i18n) — Módulo Avanzado

## Descripción general

El módulo avanzado del sistema utiliza un sistema de internacionalización (i18n) basado en archivos PHP de arrays asociativos. Cada idioma tiene su propio archivo en la carpeta `/lang/`. La función `t()` (definida en `core/helpers.php`) carga el archivo del idioma activo y devuelve la cadena de texto correspondiente a cada clave.

### Idiomas disponibles

| Código | Idioma        | Archivo           |
|--------|---------------|-------------------|
| `es`   | Español       | `lang/es.php`     |
| `en`   | English       | `lang/en.php`     |
| `de`   | Deutsch       | `lang/de.php`     |
| `fr`   | Français      | `lang/fr.php`     |
| `pt`   | Português     | `lang/pt.php`     |
| `zh`   | 中文           | `lang/zh.php`     |
| `ja`   | 日本語          | `lang/ja.php`     |
| `ko`   | 한국어          | `lang/ko.php`     |
| `hi`   | हिन्दी         | `lang/hi.php`     |
| `ar`   | العربية       | `lang/ar.php`     |

> Los idiomas `no`, `it`, `ru`, `el` y `tr` también están disponibles para otras partes del sistema.

---

## Cómo funciona el selector de idioma

### Panel principal del mapa (`/map`)

El mapa principal tiene un **botón 🌐** en la barra superior del panel lateral (id `lang-globe-btn`).

1. Al hacer clic en el botón 🌐 se abre un menú desplegable con todos los idiomas disponibles.
2. Al elegir un idioma se llama `setMapUILang(code)`, que:
   - Guarda el código en `localStorage` (`mapita_ui_lang`).
   - Recarga la página añadiendo `?lang=XX` a la URL.
3. `views/business/map.php` detecta `$_GET['lang']` y llama a `setUILanguage()`, guardando la preferencia en `$_SESSION['ui_lang']`.
4. En el lado del cliente, `MAPITA_UI_LANG` se inicializa con la siguiente prioridad: **sesión PHP → localStorage → español (`es`) por defecto**.
5. `uiStr(key)` (JS) y `t(key)` (PHP) usan el mismo idioma activo para etiquetar la interfaz.

> **Nota:** El parámetro `?lang=XX` en la URL del mapa también establece el idioma para los Módulos Avanzados, porque ambos guardan la preferencia en la misma clave de sesión (`$_SESSION['ui_lang']`).

### Módulo Avanzado (`/avanzado`, `/juridico`, `/fiscal`, etc.)

1. El usuario elige un idioma en el selector (`<select>` con banderas y nombres).
2. El formulario navega a la misma URL añadiendo `?lang=XX` (p.ej. `?lang=en`).
3. `_layout.php` detecta `$_GET['lang']` y llama a `setUILanguage()`, que guarda el código en `$_SESSION['ui_lang']`.
4. En peticiones posteriores, `getUILanguage()` lee `$_SESSION['ui_lang']`, manteniendo la preferencia durante toda la sesión sin necesitar el parámetro en cada URL.
5. Si la clave no existe en el idioma activo, `t()` hace fallback automático a español (`es`).

### Soporte RTL (Right-to-Left)

El árabe (`ar`) activa `dir="rtl"` en la etiqueta `<html>`, ajustando automáticamente el diseño para escritura de derecha a izquierda.

---

## Cómo agregar una nueva clave de texto

1. Abre `lang/es.php` y agrega la nueva clave con el texto en español:
   ```php
   'mi_nueva_clave' => 'Mi texto en español',
   ```

2. Repite el paso 1 en **todos** los archivos de idioma (`lang/en.php`, `lang/de.php`, etc.) con el texto traducido:
   ```php
   // lang/en.php
   'mi_nueva_clave' => 'My text in English',
   // lang/de.php
   'mi_nueva_clave' => 'Mein Text auf Deutsch',
   // ... y así para cada idioma
   ```

3. En la plantilla PHP que corresponda, usa la función `t()`:
   ```php
   echo htmlspecialchars(t('mi_nueva_clave'), ENT_QUOTES, 'UTF-8');
   ```

   Para títulos dinámicos:
   ```php
   siteHeader(t('mi_nueva_clave'), 'nombre_panel');
   ```

> **Nota:** Si no agregas la clave en algún idioma, `t()` hará fallback al texto en español automáticamente, evitando errores visibles para el usuario.

---

## Cómo agregar un nuevo idioma

1. **Crea el archivo de idioma:** Copia `lang/es.php` como base y renómbralo con el código ISO 639-1 del nuevo idioma (p.ej. `lang/it.php` para italiano).

2. **Traduce las cadenas:** Reemplaza los valores (no las claves) con las traducciones correspondientes.

3. **Registra el idioma en `core/helpers.php`:** Agrega el código al array `MAPITA_SUPPORTED_LANGS`:
   ```php
   const MAPITA_SUPPORTED_LANGS = [..., 'it'];
   ```

4. **Agrega el idioma al selector en `views/sites/_layout.php`:** Agrega una entrada en `ADV_LANG_OPTIONS`:
   ```php
   'it' => ['label' => 'Italiano', 'flag' => '🇮🇹'],
   ```

5. **Para RTL (árabe, hebreo, etc.):** Agrega el código al bloque de detección RTL en `siteHeader()`:
   ```php
   $isRTL = in_array($lang, ['ar', 'he'], true);
   ```

---

## Claves principales del módulo avanzado

Las siguientes claves cubren los textos clave de la oferta de asesoramiento de Pablo:

| Clave                  | Descripción                                      |
|------------------------|--------------------------------------------------|
| `advice_offer`         | Oferta de Asesoramiento                          |
| `consult_industries`   | Asesoramiento para Industrias                    |
| `consult_chambers`     | Asesoramiento para Cámaras                       |
| `brand_services`       | Servicios de Marcas                              |
| `translate_option`     | Etiqueta del selector de idioma                  |
| `adv_hub_title`        | Título del hub avanzado                          |
| `adv_hub_desc`         | Descripción del hub                              |
| `juridico_page_title`  | Título del panel jurídico                        |
| `juridico_title`       | Encabezado del módulo jurídico                   |
| `juridico_desc`        | Descripción del módulo jurídico                  |
| `fiscal_page_title`    | Título del panel fiscal                          |
| `inversion_page_title` | Título del panel de inversión                    |
| `compliance_page_title`| Título del panel de compliance                   |
| `marca_page_title`     | Título del panel de marca y expansión            |
| `tasacion_page_title`  | Título del panel de tasación de marcas           |
| `tasacion_specialist`  | Enlace a la especialista en tasación             |

---

## Calidad de las traducciones

Las traducciones iniciales son de alta calidad y están orientadas a un tono profesional para la oferta de servicios de asesoramiento internacional. Se recomienda:

- Revisar y ajustar las traducciones con hablantes nativos para los idiomas más críticos (especialmente chino, japonés, coreano, hindi y árabe).
- Para comunicaciones formales o materiales de marketing, contratar traductores especializados en terminología legal, fiscal y financiera.
- Las claves en español (`es.php`) son la fuente de verdad. Cualquier cambio en el texto base debe replicarse en todos los idiomas.

---

## Estructura de archivos

```
lang/
├── es.php      ← Español (fuente de verdad)
├── en.php      ← English
├── de.php      ← Deutsch
├── fr.php      ← Français
├── pt.php      ← Português
├── zh.php      ← 中文 (Chino simplificado)
├── ja.php      ← 日本語 (Japonés)
├── ko.php      ← 한국어 (Coreano)
├── hi.php      ← हिन्दी (Hindi)
├── ar.php      ← العربية (Árabe, RTL)
├── no.php      ← Norsk
├── it.php      ← Italiano
├── ru.php      ← Русский
├── el.php      ← Ελληνικά
└── tr.php      ← Türkçe

core/
└── helpers.php     ← t(), getUILanguage(), setUILanguage(), MAPITA_SUPPORTED_LANGS

views/sites/
├── _layout.php         ← Selector de idioma + siteHeader() + siteFooter()
├── avanzado.php        ← Hub del módulo avanzado
├── juridico.php        ← Panel jurídico
├── fiscal.php          ← Panel fiscal
├── inversion.php       ← Panel de inversión
├── compliance.php      ← Panel de compliance
├── marca_expansion.php ← Panel de marca y expansión
└── tasacion.php        ← Panel de tasación de marcas
```
