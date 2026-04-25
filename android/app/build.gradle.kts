plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "com.mapita.app"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.mapita.app"
        minSdk = 26
        targetSdk = 34
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            // Signing config: uncomment and fill in after creating a keystore.
            // signingConfig = signingConfigs.getByName("release")
        }
    }

    // Signing configs placeholder – fill in before generating a signed release APK.
    // signingConfigs {
    //     create("release") {
    //         keyAlias = System.getenv("KEY_ALIAS") ?: "your-key-alias"
    //         keyPassword = System.getenv("KEY_PASSWORD") ?: "your-key-password"
    //         storeFile = file(System.getenv("STORE_FILE") ?: "your-keystore.jks")
    //         storePassword = System.getenv("STORE_PASSWORD") ?: "your-store-password"
    //     }
    // }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions {
        jvmTarget = "17"
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.12.0")
    implementation("androidx.appcompat:appcompat:1.6.1")
}
