#!/bin/bash

# ============================================
# Script de génération des SHA keys Firebase
# Pour OUAGA CHAP Android Apps
# ============================================

echo "🔑 SHA Keys Generator pour Firebase"
echo "===================================="
echo ""

# Fonction pour afficher les SHA keys
show_sha_keys() {
    local keystore=$1
    local alias=$2
    local password=$3
    local label=$4

    echo "📱 $label"
    echo "---"
    
    if [ -f "$keystore" ]; then
        keytool -list -v -keystore "$keystore" -alias "$alias" -storepass "$password" 2>/dev/null | grep -E "SHA 1:|SHA 256:" | while read line; do
            echo "  $line"
        done
        echo ""
    else
        echo "  ⚠️ Keystore non trouvé: $keystore"
        echo ""
    fi
}

# Debug keystore (commun à toutes les apps)
echo "🔧 DEBUG KEYSTORE"
echo "================="
DEBUG_KEYSTORE="$HOME/.android/debug.keystore"
if [ -f "$DEBUG_KEYSTORE" ]; then
    keytool -list -v -keystore "$DEBUG_KEYSTORE" -storepass android 2>/dev/null | grep -E "SHA 1:|SHA 256:"
else
    echo "⚠️ Debug keystore non trouvé"
fi
echo ""

# Release keystore - Client
echo "📦 RELEASE KEYSTORE - CLIENT"
echo "============================="
CLIENT_RELEASE_KEYSTORE="./client/android/keys/release.keystore"
if [ -f "$CLIENT_RELEASE_KEYSTORE" ]; then
    echo "Trouvé: $CLIENT_RELEASE_KEYSTORE"
    echo "Entrez le mot de passe du keystore:"
    read -s CLIENT_PASS
    keytool -list -v -keystore "$CLIENT_RELEASE_KEYSTORE" -storepass "$CLIENT_PASS" 2>/dev/null | grep -E "SHA 1:|SHA 256:"
else
    echo "⚠️ Keystore release non trouvé. Pour le créer:"
    echo ""
    echo "  keytool -genkey -v -keystore client/android/keys/release.keystore \\"
    echo "    -keyalg RSA -keysize 2048 -validity 10000 \\"
    echo "    -alias ouagachap-client"
fi
echo ""

# Release keystore - Coursier
echo "📦 RELEASE KEYSTORE - COURSIER"
echo "==============================="
COURIER_RELEASE_KEYSTORE="./coursier/android/keys/release.keystore"
if [ -f "$COURIER_RELEASE_KEYSTORE" ]; then
    echo "Trouvé: $COURIER_RELEASE_KEYSTORE"
    echo "Entrez le mot de passe du keystore:"
    read -s COURIER_PASS
    keytool -list -v -keystore "$COURIER_RELEASE_KEYSTORE" -storepass "$COURIER_PASS" 2>/dev/null | grep -E "SHA 1:|SHA 256:"
else
    echo "⚠️ Keystore release non trouvé. Pour le créer:"
    echo ""
    echo "  keytool -genkey -v -keystore coursier/android/keys/release.keystore \\"
    echo "    -keyalg RSA -keysize 2048 -validity 10000 \\"
    echo "    -alias ouagachap-courier"
fi
echo ""

echo "=============================================="
echo "📋 INSTRUCTIONS"
echo "=============================================="
echo ""
echo "1. Copiez les SHA-1 et SHA-256 ci-dessus"
echo ""
echo "2. Allez sur Firebase Console:"
echo "   https://console.firebase.google.com/project/ouaga-chap/settings/general"
echo ""
echo "3. Pour chaque app Android:"
echo "   - Cliquez sur l'app"
echo "   - Cliquez 'Add fingerprint'"
echo "   - Collez le SHA-1 (sans les espaces après les :)"
echo "   - Répétez pour SHA-256"
echo ""
echo "4. Téléchargez le nouveau google-services.json"
echo ""
echo "5. Remplacez les fichiers:"
echo "   - client/android/app/google-services.json"
echo "   - coursier/android/app/google-services.json"
echo ""
echo "=============================================="
