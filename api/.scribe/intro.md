# Introduction

API REST pour l'application de livraison OUAGA CHAP - Livraison rapide à Ouagadougou

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

    Bienvenue dans la documentation de l'API OUAGA CHAP.
    
    Cette API permet de gérer les livraisons, les coursiers, les paiements et les utilisateurs de l'application OUAGA CHAP.

    ## Authentification
    L'API utilise Laravel Sanctum pour l'authentification. Après vérification OTP, vous recevez un token Bearer à utiliser dans les requêtes.

    ## Rôles utilisateurs
    - **CLIENT** : Peut créer des commandes et effectuer des paiements
    - **COURIER** : Peut accepter et livrer des commandes
    - **ADMIN** : Accès complet via le panneau Filament

    <aside>Les exemples de code sont disponibles en Bash et JavaScript dans le panneau de droite.</aside>

