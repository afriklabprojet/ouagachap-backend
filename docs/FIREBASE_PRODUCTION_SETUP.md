# 🔥 Firebase OTP - Guide de Mise en Production

## Prérequis

- Compte Firebase avec projet `ouaga-chap`
- Accès à la console Firebase: https://console.firebase.google.com/project/ouaga-chap
- Keystore de release pour Android (pour Google Play)

---

## 1. 📱 Configuration Firebase Console

### 1.1 Activer Phone Authentication

1. Aller sur [Firebase Console](https://console.firebase.google.com/project/ouaga-chap/authentication/providers)
2. **Authentication** → **Sign-in method**
3. Cliquer sur **Phone** → **Enable**
4. Sauvegarder

### 1.2 Ajouter les numéros de test (optionnel mais recommandé)

Pour tester sans consommer de SMS:
1. **Authentication** → **Sign-in method** → **Phone**
2. Section **Phone numbers for testing**
3. Ajouter: `+22670123456` avec code `123456`
4. Ajouter: `+22670200001` avec code `123456` (coursier test)

### 1.3 Configurer les domaines autorisés (pour Web)

1. **Authentication** → **Settings** → **Authorized domains**
2. Ajouter vos domaines:
   - `ouagachap.com`
   - `api.ouagachap.com`
   - `app.ouagachap.com`

---

## 2. 🤖 Configuration Android

### 2.1 Générer les SHA keys de production

```bash
# SHA-1 et SHA-256 du keystore de release
keytool -list -v -keystore ~/path/to/release-keystore.jks -alias your-alias

# Si vous n'avez pas encore de keystore, créez-en un:
keytool -genkey -v -keystore ouagachap-release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias ouagachap
```

### 2.2 Ajouter les SHA keys dans Firebase

1. Firebase Console → **Project Settings** (⚙️)
2. Section **Your apps** → Sélectionner l'app Android
3. **Add fingerprint**
4. Coller le SHA-1 et SHA-256 de release

### 2.3 Télécharger le nouveau google-services.json

Après avoir ajouté les SHA keys:
1. Cliquer sur **Download google-services.json**
2. Remplacer les fichiers:
   - `client/android/app/google-services.json`
   - `coursier/android/app/google-services.json`

### 2.4 Configurer le keystore dans build.gradle

Éditer `android/app/build.gradle`:

```groovy
android {
    ...
    signingConfigs {
        release {
            storeFile file("../keys/ouagachap-release.jks")
            storePassword System.getenv("KEYSTORE_PASSWORD") ?: ""
            keyAlias "ouagachap"
            keyPassword System.getenv("KEY_PASSWORD") ?: ""
        }
    }
    
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
}
```

---

## 3. 🍎 Configuration iOS

### 3.1 Télécharger GoogleService-Info.plist

1. Firebase Console → **Project Settings**
2. Ajouter une app iOS si pas déjà fait
3. Bundle ID: `com.ouagachap.client` / `com.ouagachap.courier`
4. Télécharger `GoogleService-Info.plist`
5. Placer dans `ios/Runner/`

### 3.2 Configurer les URL Schemes

Dans `ios/Runner/Info.plist`, ajouter:

```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLSchemes</key>
        <array>
            <!-- Copier REVERSED_CLIENT_ID de GoogleService-Info.plist -->
            <string>com.googleusercontent.apps.YOUR-CLIENT-ID</string>
        </array>
    </dict>
</array>
```

### 3.3 Configurer les Push Notifications (APNs)

1. Apple Developer Console → Certificates, Identifiers & Profiles
2. Créer un **APNs Auth Key** (.p8)
3. Firebase Console → **Project Settings** → **Cloud Messaging**
4. Section **Apple app configuration** → Upload APNs key

---

## 4. 🖥️ Configuration Backend (Laravel)

### 4.1 Variables d'environnement production

Ajouter dans `.env.production`:

```env
# Firebase
FIREBASE_CREDENTIALS=/var/www/ouagachap/storage/firebase-credentials.json
FIREBASE_PROJECT=ouaga-chap

# OTP Configuration
AUTH_OTP_DRIVER=firebase
# Fallback SMS si Firebase échoue
SMS_FALLBACK_ENABLED=true
```

### 4.2 Uploader le fichier credentials

```bash
# Sur le serveur de production
scp storage/firebase-credentials.json user@server:/var/www/ouagachap/storage/

# Sécuriser les permissions
chmod 600 /var/www/ouagachap/storage/firebase-credentials.json
chown www-data:www-data /var/www/ouagachap/storage/firebase-credentials.json
```

### 4.3 Vérifier la configuration

```bash
php artisan tinker
>>> app('firebase.auth')->listUsers()->getIterator()->current()
# Doit retourner un utilisateur Firebase ou null
```

---

## 5. 🔧 Configuration Flutter

### 5.1 Modifier les constantes de production

Éditer `lib/core/constants/app_constants.dart`:

```dart
class ApiConstants {
  // Production
  static const String baseUrl = 'https://api.ouagachap.com/api/v1';
  
  // Désactiver le mode démo
  static const bool demoMode = false;
}
```

### 5.2 Configurer Firebase Options

Les fichiers `firebase_options.dart` sont générés automatiquement avec FlutterFire CLI:

```bash
# Installer FlutterFire CLI
dart pub global activate flutterfire_cli

# Configurer (dans chaque app)
cd client
flutterfire configure --project=ouaga-chap

cd ../coursier
flutterfire configure --project=ouaga-chap
```

---

## 6. 🧪 Tests avant déploiement

### 6.1 Test Firebase Auth local

```bash
# Tester l'envoi OTP (remplacer le numéro)
curl -X POST https://api.ouagachap.com/api/v1/auth/otp/send \
  -H "Content-Type: application/json" \
  -d '{"phone": "+22670123456"}'
```

### 6.2 Test avec numéro de test Firebase

1. Utiliser un numéro configuré dans Firebase Console
2. Entrer le code de test (123456)
3. Vérifier que l'authentification fonctionne

### 6.3 Test avec vrai numéro

1. Utiliser un vrai numéro burkinabè (+226...)
2. Recevoir le SMS de Firebase
3. Entrer le code reçu

---

## 7. 📊 Monitoring & Quotas

### 7.1 Surveiller l'utilisation

Firebase Console → **Authentication** → **Usage**

### 7.2 Quotas Firebase Phone Auth (gratuit)

- **10 000 vérifications/mois** gratuites
- Au-delà: $0.01 - $0.06 par vérification

### 7.3 Configurer des alertes

Firebase Console → **Project Settings** → **Usage and billing** → **Set budget alert**

---

## 8. 🚨 Dépannage

### Erreur "App not authorized"

1. Vérifier que les SHA keys sont ajoutées
2. Re-télécharger google-services.json
3. Rebuild l'app: `flutter clean && flutter build`

### Erreur "Too many requests"

Firebase rate-limite les SMS. Attendre ou utiliser les numéros de test.

### Erreur "Invalid phone number"

Le numéro doit être au format E.164: `+22670123456`

### SMS non reçu

1. Vérifier que le pays est supporté (Burkina Faso ✅)
2. Vérifier les quotas Firebase
3. Tester avec un autre numéro

---

## 9. ✅ Checklist finale

- [ ] Phone Auth activé dans Firebase Console
- [ ] SHA-1 et SHA-256 de release ajoutées (Android)
- [ ] google-services.json mis à jour (Android)
- [ ] GoogleService-Info.plist configuré (iOS)
- [ ] APNs key uploadée (iOS)
- [ ] firebase-credentials.json déployé sur serveur
- [ ] Variables .env configurées
- [ ] Tests avec numéros de test réussis
- [ ] Test avec vrai numéro réussi
- [ ] Monitoring configuré

---

## 📞 Support

En cas de problème:
1. Vérifier les logs Firebase: Console → **Authentication** → **Logs**
2. Vérifier les logs Laravel: `storage/logs/laravel.log`
3. Vérifier la console Flutter: `flutter logs`
