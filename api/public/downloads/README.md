# APK Downloads Directory

Ce dossier contient les fichiers APK pour les applications OUAGA CHAP.

## Fichiers attendus

1. **ouaga-chap-client.apk** - Application client pour commander des livraisons
2. **ouaga-chap-coursier.apk** - Application coursier pour effectuer des livraisons

## Comment générer les APKs

### Application Client
```bash
cd client
flutter build apk --release
# L'APK sera généré dans: build/app/outputs/flutter-apk/app-release.apk
# Renommer et copier ici: ouaga-chap-client.apk
```

### Application Coursier
```bash
cd coursier
flutter build apk --release
# L'APK sera généré dans: build/app/outputs/flutter-apk/app-release.apk
# Renommer et copier ici: ouaga-chap-coursier.apk
```

## Notes

- Les APKs doivent être signés pour la production
- Taille approximative: 25-30 MB par APK
- Versions iOS disponibles sur demande via TestFlight
