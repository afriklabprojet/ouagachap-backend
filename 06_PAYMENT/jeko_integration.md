# JEKO Payment Integration - OUAGA CHAP
## Configuration Complète

### 1. Prérequis
- Compte partenaire JEKO Africa (https://developer.jeko.africa)
- Clés API (API Key + API Key ID)
- Store ID
- Webhook Secret

### 2. Configuration Backend (.env)

```env
# JEKO Payment Gateway
JEKO_API_KEY=votre_api_key
JEKO_API_KEY_ID=votre_api_key_id
JEKO_STORE_ID=votre_store_id
JEKO_WEBHOOK_SECRET=votre_webhook_secret
JEKO_BASE_URL=https://api.jeko.africa
JEKO_SANDBOX=true
APP_SCHEME=ouagachap
```

### 3. Endpoints API

#### Méthodes de paiement
```
GET /api/v1/jeko/payment-methods
Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "data": [
    {"code": "wave", "name": "Wave", "icon": "🌊"},
    {"code": "orange", "name": "Orange Money", "icon": "🟠"},
    {"code": "mtn", "name": "MTN Mobile Money", "icon": "🟡"},
    {"code": "moov", "name": "Moov Money", "icon": "🔵"},
    {"code": "djamo", "name": "Djamo", "icon": "💳"}
  ]
}
```

#### Recharge Wallet
```
POST /api/v1/jeko/recharge
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 5000,
  "payment_method": "wave"
}
```

Response:
```json
{
  "success": true,
  "message": "Paiement initié avec succès",
  "data": {
    "transaction_id": 123,
    "jeko_id": "jeko_xxx",
    "redirect_url": "https://pay.jeko.africa/...",
    "amount": 5000,
    "payment_method": "wave"
  }
}
```

#### Paiement Commande
```
POST /api/v1/jeko/pay-order
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_id": "uuid-xxx",
  "payment_method": "orange"
}
```

#### Statut Transaction
```
GET /api/v1/jeko/status/{transactionId}
Authorization: Bearer {token}
```

#### Historique Transactions
```
GET /api/v1/jeko/transactions?page=1
Authorization: Bearer {token}
```

#### Webhook JEKO
```
POST /api/v1/jeko/webhook
Header: Jeko-Signature: {hmac_sha256}
```

### 4. Configuration Flutter

#### Deep Links (iOS - Info.plist)
```xml
<key>CFBundleURLTypes</key>
<array>
  <dict>
    <key>CFBundleURLSchemes</key>
    <array>
      <string>ouagachap</string>
    </array>
  </dict>
</array>
```

#### Deep Links (Android - AndroidManifest.xml)
```xml
<intent-filter>
  <action android:name="android.intent.action.VIEW"/>
  <category android:name="android.intent.category.DEFAULT"/>
  <category android:name="android.intent.category.BROWSABLE"/>
  <data android:scheme="ouagachap"/>
</intent-filter>
```

### 5. Flux de Paiement

1. Client sélectionne montant et méthode de paiement
2. App appelle POST /api/v1/jeko/recharge
3. Backend crée transaction locale + appelle JEKO API
4. Backend retourne redirect_url
5. App ouvre redirect_url dans navigateur externe
6. Client complète paiement dans app mobile money
7. JEKO redirige vers ouagachap://payment/success?transaction_id=xxx
8. App reçoit deep link et vérifie statut
9. Webhook JEKO confirme le paiement (en parallèle)
10. Wallet crédité et notification envoyée

### 6. Sécurité

- Signature HMAC-SHA256 sur les webhooks
- Validation des montants min/max (100 - 1,000,000 FCFA)
- Rate limiting sur les endpoints
- Logs détaillés de toutes les transactions
- Double vérification (callback + webhook)

### 7. Méthodes de Paiement Supportées

| Code | Nom | Pays |
|------|-----|------|
| wave | Wave | BF, CI, SN, ML |
| orange | Orange Money | BF, CI, SN, ML, NE, GN |
| mtn | MTN MoMo | CI, GH, CM, BJ |
| moov | Moov Money | BF, CI, BJ, TG, NE |
| djamo | Djamo | CI |

### 8. Fichiers Créés

**Backend (Laravel):**
- `config/jeko.php` - Configuration
- `app/Services/JekoPaymentService.php` - Service principal
- `app/Models/JekoTransaction.php` - Modèle transaction
- `app/Http/Controllers/Api/V1/JekoPaymentController.php` - Controller API
- `app/Http/Controllers/Api/V1/JekoWebhookController.php` - Webhook handler
- `database/migrations/2026_01_25_100000_create_jeko_transactions_table.php`

**Frontend (Flutter):**
- `features/wallet/data/datasources/jeko_payment_datasource.dart`
- `features/wallet/data/repositories/jeko_payment_repository.dart`
- `features/wallet/presentation/bloc/jeko_payment_bloc.dart`
- `features/wallet/presentation/bloc/jeko_payment_event.dart`
- `features/wallet/presentation/bloc/jeko_payment_state.dart`
- `features/wallet/presentation/pages/jeko_recharge_page.dart`
- `features/wallet/presentation/pages/jeko_transaction_history_page.dart`
- `features/wallet/presentation/widgets/jeko_payment_method_selector.dart`
- `core/services/jeko_deep_link_handler.dart`

### 9. Navigation Flutter

Routes ajoutées:
- `/home/jeko-recharge` - Page de recharge Mobile Money
- `/home/jeko-history` - Historique des transactions JEKO
