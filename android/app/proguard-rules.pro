# Add project specific ProGuard rules here.
# You can control the set of applied configuration files using the
# proguardFiles setting in build.gradle.kts.

# If your project uses WebView with JS, uncomment the following
# and specify the fully qualified class name to the JavaScript interface class:
# -keepclassmembers class fqcn.of.javascript.interface.for.webview {
#   public *;
# }

# Keep the MainActivity
-keep class com.mapita.app.MainActivity { *; }
