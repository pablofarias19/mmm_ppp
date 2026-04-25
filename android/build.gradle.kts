apply plugin: "com.android.application"

android {
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
            minifyEnabled = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
        }
    }
}

dependencies {
    implementation("org.jetbrains.kotlin:kotlin-stdlib:1.7.10")
    implementation("androidx.appcompat:appcompat:1.6.1")
    implementation("androidx.core:core-ktx:1.10.1")
}

// Add signing config placeholder
android {
    signingConfigs {
        release {
            keyAlias = "your-key-alias"
            keyPassword = "your-key-password"
            storeFile = file("your-keystore-file.jks")
            storePassword = "your-store-password"
        }
    }
}
