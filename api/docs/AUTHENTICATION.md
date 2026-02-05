# Système d'Authentification

## Vue d'ensemble

OUAGA CHAP utilise une authentification **sans mot de passe** basée sur:
- Numéro de téléphone
- Code OTP (One-Time Password) par SMS
- Tokens Laravel Sanctum

## Flux d'authentification

```
┌─────────┐     1. POST /auth/otp/send      ┌─────────┐
│  User   │ ─────────────────────────────── │   API   │
│  (App)  │ ◄─────────────────────────────  │         │
└─────────┘     2. OTP envoyé par SMS       └─────────┘
     │                                            │
     │          3. Reçoit SMS                     │
     ▼                                            │
┌─────────┐     4. POST /auth/otp/verify    ┌─────────┐
│  User   │ ─────────────────────────────── │   API   │
│  (App)  │ ◄─────────────────────────────  │         │
└─────────┘     5. Token + User data        └─────────┘
```

## Rôles utilisateurs

| Rôle | Description | Application |
|------|-------------|-------------|
| `customer` | Client qui commande des livraisons | App Client |
| `courier` | Coursier qui effectue les livraisons | App Coursier |
| `admin` | Administrateur du système | Panel Filament |

## Séparation des applications

Le paramètre `app_type` dans `/otp/verify` assure que:
- Un **client** ne peut pas se connecter à l'app **coursier**
- Un **coursier** ne peut pas se connecter à l'app **client**
- Un **admin** ne peut se connecter qu'au panel web

```php
// Validation dans AuthService
if ($user->role === UserRole::CUSTOMER && $appType !== 'client') {
    throw new UnauthorizedException('Utilisez l\'application client');
}

if ($user->role === UserRole::COURIER && $appType !== 'courier') {
    throw new UnauthorizedException('Utilisez l\'application coursier');
}
```

## OTP en développement

En mode `APP_ENV=local`, le code **123456** fonctionne toujours:

```php
// AuthService.php
if (app()->environment('local') && $otp === '123456') {
    return true; // OTP valide en dev
}
```

## Génération d'OTP

```php
// Génération d'un OTP à 6 chiffres
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Stockage avec expiration (5 minutes)
OtpCode::create([
    'phone' => $phone,
    'code' => Hash::make($otp),
    'expires_at' => now()->addMinutes(5),
]);
```

## Normalisation des numéros

Tous les numéros sont normalisés au format E.164:

```php
// +22670123456 (format international)
public function normalizePhone(string $phone): string
{
    // Supprimer les espaces et caractères spéciaux
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Ajouter le préfixe Burkina Faso si nécessaire
    if (!str_starts_with($phone, '+')) {
        if (str_starts_with($phone, '226')) {
            $phone = '+' . $phone;
        } else {
            $phone = '+226' . $phone;
        }
    }
    
    return $phone;
}
```

## Tokens Sanctum

### Création du token

```php
$token = $user->createToken('auth-token', ['*'])->plainTextToken;
```

### Utilisation dans les requêtes

```http
GET /api/v1/profile
Authorization: Bearer 1|abc123def456...
```

### Révocation du token (déconnexion)

```php
// Révoquer le token actuel
$request->user()->currentAccessToken()->delete();

// Révoquer tous les tokens
$user->tokens()->delete();
```

## Middlewares de protection

### Routes API

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    // Routes authentifiées
});

Route::middleware(['auth:sanctum', 'role.client'])->group(function () {
    // Routes client uniquement
});

Route::middleware(['auth:sanctum', 'role.courier'])->group(function () {
    // Routes coursier uniquement
});
```

### Middleware EnsureIsClient

```php
class EnsureIsClient
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role !== UserRole::CUSTOMER) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux clients'
            ], 403);
        }
        
        return $next($request);
    }
}
```

## Inscription Coursier

Les coursiers ont un processus d'inscription spécial avec vérification de documents:

```php
// Étapes d'inscription
1. Créer compte avec téléphone + OTP
2. Soumettre documents (CNIB, permis, carte grise)
3. Vérification par admin (status: pending → active)
4. Coursier peut commencer à travailler

// Statuts coursier
UserStatus::PENDING   // En attente de vérification
UserStatus::ACTIVE    // Vérifié, peut travailler
UserStatus::SUSPENDED // Suspendu par admin
```

## Sécurité

### Rate Limiting

```php
// 5 tentatives OTP par minute
RateLimiter::for('otp', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

### Expiration OTP

- Durée de vie: 5 minutes
- Suppression après utilisation
- Maximum 3 tentatives échouées

### Protection CORS

```php
// config/cors.php
'allowed_origins' => ['*'], // À restreindre en production
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

## Gestion des erreurs

```json
// OTP expiré
{
    "success": false,
    "message": "Code OTP expiré. Veuillez en demander un nouveau."
}

// OTP invalide
{
    "success": false,
    "message": "Code OTP invalide."
}

// Mauvaise application
{
    "success": false,
    "message": "Veuillez utiliser l'application client."
}

// Compte suspendu
{
    "success": false,
    "message": "Votre compte a été suspendu. Contactez le support."
}
```
