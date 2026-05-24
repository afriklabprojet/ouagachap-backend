<?php

return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Performance monitoring (0.0–1.0) — 10% par défaut
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Profiling (0.0–1.0) — désactivé par défaut (coûteux)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // Désactivé pour conformité RGPD (pas d'IP, pas d'email auto)
    'send_default_pii' => false,

    'environment' => env('APP_ENV', 'production'),

    // Mettre à jour à chaque déploiement via CI/CD
    'release' => env('SENTRY_RELEASE'),

    'in_app_exclude' => [base_path('vendor')],

    // Exceptions non-actionnables — bruit inutile dans Sentry
    'ignore_exceptions' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        Illuminate\Auth\AuthenticationException::class,
        Illuminate\Auth\Access\AuthorizationException::class,
        Illuminate\Validation\ValidationException::class,
    ],

    // Attacher le contexte utilisateur sans PII sensible
    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        if (auth()->check()) {
            $user = auth()->user();
            $event->setUser(new \Sentry\UserDataBag(
                id: (string) $user->id,
                // Derniers 4 chiffres du téléphone seulement — pas de PII complète
                username: $user->phone ? ('****' . substr($user->phone, -4)) : null,
                metadata: ['role' => $user->role?->value ?? 'unknown'],
            ));
        }
        return $event;
    },

    'breadcrumbs' => [
        'logs'                => true,
        'cache'               => true,
        'livewire'            => false,
        'sql_queries'         => true,
        'sql_bindings'        => false, // pas de données sensibles dans les breadcrumbs
        'queue_info'          => true,
        'command_info'        => true,
        'http_client_requests'=> true,
    ],

    'tracing' => [
        'queue_job_transactions' => true,
        'queue_jobs'             => true,
        'sql_queries'            => true,
        'sql_origin'             => true,
        'http_client_requests'   => true,
        'redis_commands'         => false,
        'views'                  => false,
    ],

];
