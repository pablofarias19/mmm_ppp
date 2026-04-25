# MAPITA – Android WebView App

App nativa Android que carga el sitio **https://www.mapita.com.ar** dentro de un WebView de pantalla completa.

---

## Requisitos

| Herramienta | Versión mínima |
|---|---|
| Android Studio | Flamingo (2022.2.1) o superior |
| JDK | 17 |
| Android SDK compileSdk | 34 |
| minSdk | 26 (Android 8.0 Oreo) |

---

## Abrir en Android Studio

1. Abrí Android Studio.
2. Elegí **File → Open** y seleccioná la carpeta **`android/`** (no la raíz del repo).
3. Esperá a que Gradle sincronice (puede tardar unos minutos la primera vez, descarga dependencias).

---

## Generar APK debug

### Desde Android Studio
**Build → Build Bundle(s) / APK(s) → Build APK(s)**

El APK queda en:
```
android/app/build/outputs/apk/debug/app-debug.apk
```

### Desde la terminal
```bash
cd android
./gradlew :app:assembleDebug
```

---

## Generar APK release (sin firmar)

```bash
cd android
./gradlew :app:assembleRelease
```

El APK queda en:
```
android/app/build/outputs/apk/release/app-release-unsigned.apk
```

---

## Firmar el APK release

### 1. Crear un keystore (sólo la primera vez)

```bash
keytool -genkey -v \
  -keystore mapita-release.jks \
  -alias mapita \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000
```

### 2. Configurar signing en `app/build.gradle.kts`

Descomentá el bloque `signingConfigs` en `android/app/build.gradle.kts` y completá los valores,
**o** exportalos como variables de entorno para no exponer credenciales en el código:

```kotlin
signingConfigs {
    create("release") {
        keyAlias     = System.getenv("KEY_ALIAS")     ?: "mapita"
        keyPassword  = System.getenv("KEY_PASSWORD")  ?: "tu-clave"
        storeFile    = file(System.getenv("STORE_FILE") ?: "mapita-release.jks")
        storePassword = System.getenv("STORE_PASSWORD") ?: "tu-store-password"
    }
}
```

También descomentá `signingConfig = signingConfigs.getByName("release")` dentro del bloque `release`.

### 3. Volver a generar el APK release firmado

```bash
cd android
./gradlew :app:assembleRelease
```

El APK firmado quedará en `android/app/build/outputs/apk/release/app-release.apk`.

---

## Reemplazar el ícono (logoapp.png)

1. Abrí el proyecto en Android Studio.
2. Clic derecho en `app/src/main/res` → **New → Image Asset**.
3. Elegí **Launcher Icons (Adaptive and Legacy)**.
4. En **Foreground Layer** seleccioná tu `logoapp.png`.
5. Android Studio generará automáticamente todos los tamaños en `mipmap-*`.

> Por ahora el ícono es un placeholder con la letra "M" en azul.
> Los archivos a reemplazar son:
> - `app/src/main/res/drawable/ic_launcher_foreground.xml`
> - `app/src/main/res/drawable/ic_launcher_background.xml`

---

## Notas de seguridad

- `android:usesCleartextTraffic="false"` en el Manifest: la app sólo acepta HTTPS.
  Si el sitio sirve recursos por HTTP, agregá un `res/xml/network_security_config.xml`
  y referencialo con `android:networkSecurityConfig` en la etiqueta `<application>`.
- **No comitees** el archivo `.jks` ni credenciales al repositorio.

---

## Estructura del proyecto

```
android/
├── build.gradle.kts            # Plugins del proyecto
├── settings.gradle.kts         # Módulos incluidos
├── gradle.properties
├── gradlew / gradlew.bat
├── gradle/wrapper/
│   ├── gradle-wrapper.jar
│   └── gradle-wrapper.properties
└── app/
    ├── build.gradle.kts        # Configuración del módulo
    ├── proguard-rules.pro
    └── src/main/
        ├── AndroidManifest.xml
        ├── java/com/mapita/app/
        │   └── MainActivity.kt
        └── res/
            ├── drawable/       # Capas del ícono adaptativo
            ├── layout/         # activity_main.xml (WebView fullscreen)
            ├── mipmap-*/       # Íconos generados
            └── values/         # strings.xml, themes.xml
```
