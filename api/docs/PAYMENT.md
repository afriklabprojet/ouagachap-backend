# Système de Paiement

## Vue d'ensemble

OUAGA CHAP supporte les paiements Mobile Money via:
- **Orange Money** (Burkina Faso)
- **Moov Money** (Burkina Faso)
- **Paiement en espèces** à la livraison

L'intégration se fait via la passerelle **Jeko** pour les paiements électroniques.

## Flux de paiement

```
┌─────────┐     1. Créer commande        ┌─────────┐
│ Client  │ ─────────────────────────── │   API   │
│  (App)  │                              │         │
└─────────┘                              └─────────┘
     │                                        │
     │      2. Initier paiement               │
     │ ─────────────────────────────────────► │
     │                                        │
     │      3. Redirection USSD               │
     │ ◄──────────────────────────────────── │
     │                                        │
     ▼                                        │
┌─────────┐     4. Validation PIN       ┌─────────┐
│ Mobile  │ ─────────────────────────── │  Jeko   │
│ Money   │ ◄───────────────────────────│   API   │
└─────────┘     5. Confirmation          └─────────┘
     │                                        │
     │      6. Webhook confirmation           │
     └────────────────────────────────────────┤
                                              │
     ┌────────────────────────────────────────┘
     │      7. Notifier client & coursier
     ▼
┌─────────┐
│   API   │
└─────────┘
```

## Configuration

### Variables d'environnement

```env
# Jeko Payment Gateway
JEKO_API_URL=https://api.jfranco.com
JEKO_API_KEY=your_api_key
JEKO_MERCHANT_ID=your_merchant_id
JEKO_WEBHOOK_SECRET=your_webhook_secret

# Mode sandbox pour tests
JEKO_SANDBOX=true
```

### Configuration (config/jeko.php)

```php
return [
    'api_url' => env('JEKO_API_URL', 'https://api.jfranco.com'),
    'api_key' => env('JEKO_API_KEY'),
    'merchant_id' => env('JEKO_MERCHANT_ID'),
    'webhook_secret' => env('JEKO_WEBHOOK_SECRET'),
    'sandbox' => env('JEKO_SANDBOX', false),
    
    'providers' => [
        'orange_money' => [
            'code' => 'OM',
            'name' => 'Orange Money',
            'country' => 'BF',
        ],
        'moov_money' => [
            'code' => 'MOOV',
            'name' => 'Moov Money',
            'country' => 'BF',
        ],
    ],
];
```

## Endpoints API

### Initier un paiement

```http
POST /api/v1/payments/initiate
```

```json
{
    "order_id": "ORD-2026-00001",
    "payment_method": "orange_money",
    "phone": "+22670123456"
}
```

**Réponse:**
```json
{
    "success": true,
    "message": "Paiement initié",
    "data": {
        "payment_id": 1,
        "status": "pending",
        "amount": 1500,
        "ussd_code": "*144*1*1*1500#",
        "instructions": "Composez le code USSD pour valider le paiement"
    }
}
```

### Vérifier le statut

```http
GET /api/v1/payments/{id}/status
```

### Webhook Jeko

```http
POST /api/v1/webhooks/jeko
```

## JekoPaymentService

```php
class JekoPaymentService
{
    public function initiatePayment(Order $order, string $method, string $phone): array
    {
        // 1. Créer la transaction locale
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->client_id,
            'amount' => $order->price,
            'method' => $method,
            'status' => PaymentStatus::PENDING,
        ]);
        
        // 2. Appeler l'API Jeko
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('jeko.api_key'),
        ])->post(config('jeko.api_url') . '/payments', [
            'merchant_id' => config('jeko.merchant_id'),
            'amount' => $order->price,
            'currency' => 'XOF',
            'provider' => $this->getProviderCode($method),
            'phone' => $phone,
            'reference' => $payment->id,
            'callback_url' => route('webhooks.jeko'),
        ]);
        
        // 3. Mettre à jour avec la référence externe
        $payment->update([
            'external_reference' => $response['transaction_id'],
            'status' => PaymentStatus::PROCESSING,
        ]);
        
        return [
            'payment_id' => $payment->id,
            'ussd_code' => $response['ussd_code'],
        ];
    }
    
    public function handleWebhook(array $data): void
    {
        $payment = Payment::where('external_reference', $data['transaction_id'])->first();
        
        if ($data['status'] === 'SUCCESS') {
            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'completed_at' => now(),
            ]);
            
            // Déclencher les événements
            event(new PaymentCompleted($payment));
            
        } elseif ($data['status'] === 'FAILED') {
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'failure_reason' => $data['message'],
            ]);
        }
    }
}
```

## Mode Mock (Développement)

En mode `APP_ENV=local`, les paiements sont simulés:

```php
class PaymentService
{
    public function processPayment(Payment $payment): bool
    {
        // En mode local, simuler le succès
        if (app()->environment('local')) {
            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'completed_at' => now(),
            ]);
            
            event(new PaymentCompleted($payment));
            return true;
        }
        
        // En production, utiliser Jeko
        return $this->jekoService->processPayment($payment);
    }
}
```

## Portefeuille Coursier

### Crédit automatique après livraison

```php
// Listener: SendPaymentNotification
public function handle(PaymentCompleted $event)
{
    $payment = $event->payment;
    $order = $payment->order;
    
    if ($order->courier_id) {
        // Calculer la commission (80% pour le coursier)
        $courierShare = (int) ($payment->amount * 0.80);
        
        // Créditer le portefeuille
        $this->walletService->credit(
            $order->courier->wallet,
            $courierShare,
            "Livraison {$order->uuid}"
        );
    }
}
```

### Demande de retrait

```php
class WalletService
{
    public function requestWithdrawal(Wallet $wallet, int $amount, string $method, string $phone): Withdrawal
    {
        if ($amount > $wallet->balance) {
            throw new InsufficientBalanceException();
        }
        
        // Créer la demande
        $withdrawal = Withdrawal::create([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'payment_method' => $method,
            'phone' => $phone,
            'status' => 'pending',
        ]);
        
        // Bloquer le montant
        $wallet->decrement('balance', $amount);
        
        return $withdrawal;
    }
}
```

## Statuts de paiement

| Statut | Description |
|--------|-------------|
| `pending` | Paiement créé, en attente d'initiation |
| `processing` | USSD envoyé, en attente de validation |
| `completed` | Paiement réussi |
| `failed` | Paiement échoué |

## Sécurité

### Vérification du webhook

```php
public function verifyWebhookSignature(Request $request): bool
{
    $signature = $request->header('X-Jeko-Signature');
    $payload = $request->getContent();
    
    $expectedSignature = hash_hmac(
        'sha256',
        $payload,
        config('jeko.webhook_secret')
    );
    
    return hash_equals($expectedSignature, $signature);
}
```

### Idempotence

```php
// Éviter les doubles crédits
public function handleWebhook(array $data): void
{
    $payment = Payment::where('external_reference', $data['transaction_id'])->first();
    
    // Vérifier si déjà traité
    if ($payment->status === PaymentStatus::COMPLETED) {
        return; // Déjà traité
    }
    
    // Traiter...
}
```
