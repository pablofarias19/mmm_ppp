# Firma de la app (Release)

## 1. Crear el keystore (solo la primera vez)

```bash
keytool -genkey -v \
  -keystore mapita-release.jks \
  -alias mapita \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000
```

Guardá el archivo `.jks` en un lugar seguro **fuera** del repositorio.

## 2. Variables de entorno requeridas

El archivo `build.gradle.kts` lee las credenciales desde variables de entorno para no
exponerlas en el código fuente. Definí las siguientes antes de compilar:

| Variable        | Descripción                                      |
|-----------------|--------------------------------------------------|
| `KEY_ALIAS`     | Alias elegido al crear el keystore (ej. `mapita`) |
| `KEY_PASSWORD`  | Contraseña de la clave privada                   |
| `STORE_FILE`    | Ruta absoluta al archivo `.jks`                  |
| `STORE_PASSWORD`| Contraseña del keystore                          |

Ejemplo (Linux/macOS):
```bash
export KEY_ALIAS=mapita
export KEY_PASSWORD=tu_clave_password
export STORE_FILE=/ruta/al/mapita-release.jks
export STORE_PASSWORD=tu_store_password
```

## 3. Generar el AAB firmado (para Google Play)

```bash
cd android
./gradlew :app:bundleRelease
```

El archivo resultante estará en:
`app/build/outputs/bundle/release/app-release.aab`

## 4. Generar APK firmado (distribución directa)

```bash
cd android
./gradlew :app:assembleRelease
```

El archivo resultante estará en:
`app/build/outputs/apk/release/app-release.apk`
