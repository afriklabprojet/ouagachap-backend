<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    protected $signature = 'push:test 
                            {--user= : ID de l\'utilisateur} 
                            {--topic= : Nom du topic}
                            {--title=Test Notification : Titre de la notification}
                            {--body=Ceci est un test : Corps de la notification}';

    protected $description = 'Tester l\'envoi de notifications push';

    public function handle(PushNotificationService $pushService): int
    {
        $title = $this->option('title');
        $body = $this->option('body');
        $data = ['type' => 'test', 'timestamp' => now()->toIso8601String()];

        $this->info("📱 Test de notification push");
        $this->line("   Titre: $title");
        $this->line("   Message: $body");

        // Envoi vers un utilisateur spécifique
        if ($userId = $this->option('user')) {
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("❌ Utilisateur #$userId non trouvé");
                return 1;
            }

            if (!$user->fcm_token) {
                $this->error("❌ L'utilisateur {$user->name} n'a pas de token FCM");
                return 1;
            }

            $this->line("   Destinataire: {$user->name} (#{$user->id})");
            $this->line("   Token: " . substr($user->fcm_token, 0, 30) . "...");

            $result = $pushService->sendToUser($user, $title, $body, $data);
            
            if ($result) {
                $this->info("✅ Notification envoyée avec succès !");
            } else {
                $this->error("❌ Échec de l'envoi");
            }

            return $result ? 0 : 1;
        }

        // Envoi vers un topic
        if ($topic = $this->option('topic')) {
            $this->line("   Topic: $topic");

            $result = $pushService->sendToTopic($topic, $title, $body, $data);
            
            if ($result) {
                $this->info("✅ Notification envoyée au topic '$topic' !");
            } else {
                $this->error("❌ Échec de l'envoi au topic");
            }

            return $result ? 0 : 1;
        }

        // Envoi vers tous les utilisateurs avec token
        $this->line("   Mode: Tous les utilisateurs avec token FCM");

        $users = User::whereNotNull('fcm_token')->get();
        
        if ($users->isEmpty()) {
            $this->warn("⚠️ Aucun utilisateur avec token FCM trouvé");
            return 0;
        }

        $this->line("   Utilisateurs: {$users->count()}");

        $results = $pushService->sendToUsers($users->all(), $title, $body, $data);
        
        $this->info("✅ Envoyés: {$results['success']} | ❌ Échecs: {$results['failed']}");

        return 0;
    }
}
