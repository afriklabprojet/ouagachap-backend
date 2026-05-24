<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('🔧 Création des permissions et rôles...');
        $this->command->newLine();

        // ==================== PERMISSIONS ====================
        
        $permissions = [
            // Orders
            'view_orders' => 'Voir les commandes',
            'create_orders' => 'Créer des commandes',
            'edit_orders' => 'Modifier les commandes',
            'delete_orders' => 'Supprimer les commandes',
            'assign_orders' => 'Assigner les commandes',
            'cancel_orders' => 'Annuler les commandes',
            
            // Users / Clients
            'view_users' => 'Voir les utilisateurs',
            'create_users' => 'Créer des utilisateurs',
            'edit_users' => 'Modifier les utilisateurs',
            'delete_users' => 'Supprimer les utilisateurs',
            'suspend_users' => 'Suspendre les utilisateurs',
            
            // Couriers
            'view_couriers' => 'Voir les coursiers',
            'create_couriers' => 'Créer des coursiers',
            'edit_couriers' => 'Modifier les coursiers',
            'delete_couriers' => 'Supprimer les coursiers',
            'verify_couriers' => 'Vérifier les coursiers',
            
            // Payments
            'view_payments' => 'Voir les paiements',
            'refund_payments' => 'Rembourser les paiements',
            
            // Withdrawals
            'view_withdrawals' => 'Voir les retraits',
            'approve_withdrawals' => 'Approuver les retraits',
            'reject_withdrawals' => 'Rejeter les retraits',
            
            // Zones
            'view_zones' => 'Voir les zones',
            'create_zones' => 'Créer des zones',
            'edit_zones' => 'Modifier les zones',
            'delete_zones' => 'Supprimer les zones',
            
            // Promo Codes
            'view_promo_codes' => 'Voir les codes promo',
            'create_promo_codes' => 'Créer des codes promo',
            'edit_promo_codes' => 'Modifier les codes promo',
            'delete_promo_codes' => 'Supprimer les codes promo',
            
            // Ratings
            'view_ratings' => 'Voir les évaluations',
            'delete_ratings' => 'Supprimer les évaluations',
            
            // Complaints
            'view_complaints' => 'Voir les réclamations',
            'handle_complaints' => 'Traiter les réclamations',
            'delete_complaints' => 'Supprimer les réclamations',
            
            // Support Chats
            'view_support_chats' => 'Voir les chats support',
            'respond_support_chats' => 'Répondre aux chats support',
            
            // FAQs
            'view_faqs' => 'Voir les FAQs',
            'create_faqs' => 'Créer des FAQs',
            'edit_faqs' => 'Modifier les FAQs',
            'delete_faqs' => 'Supprimer les FAQs',
            
            // Traffic Incidents
            'view_traffic_incidents' => 'Voir les incidents trafic',
            'manage_traffic_incidents' => 'Gérer les incidents trafic',
            
            // Activity Logs
            'view_activity_logs' => 'Voir les logs d\'activité',
            
            // Settings
            'view_settings' => 'Voir les paramètres',
            'edit_settings' => 'Modifier les paramètres',
            
            // Reports
            'view_reports' => 'Voir les rapports',
            'export_reports' => 'Exporter les rapports',
            
            // Notifications
            'send_notifications' => 'Envoyer des notifications',
            'view_notifications' => 'Voir les notifications',
            
            // Admins Management
            'view_admins' => 'Voir les administrateurs',
            'create_admins' => 'Créer des administrateurs',
            'edit_admins' => 'Modifier les administrateurs',
            'delete_admins' => 'Supprimer les administrateurs',
            
            // Roles Management
            'view_roles' => 'Voir les rôles',
            'create_roles' => 'Créer des rôles',
            'edit_roles' => 'Modifier les rôles',
            'delete_roles' => 'Supprimer les rôles',
        ];

        $createdPermissions = 0;
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );
            $createdPermissions++;
        }
        $this->command->info("✅ {$createdPermissions} permissions créées/vérifiées");

        // ==================== ROLES ====================
        
        $roles = [
            // Super Admin - Accès total
            'super_admin' => [
                'description' => 'Administrateur principal avec accès total',
                'permissions' => Permission::all()->pluck('name')->toArray(),
            ],
            
            // Admin Support - Gestion clients et commandes
            'support' => [
                'description' => 'Support client - Gère les réclamations et aide les utilisateurs',
                'permissions' => [
                    'view_orders', 'edit_orders', 'cancel_orders',
                    'view_users', 'edit_users', 'suspend_users',
                    'view_couriers', 'edit_couriers',
                    'view_payments',
                    'view_ratings', 'delete_ratings',
                    'view_complaints', 'handle_complaints',
                    'view_support_chats', 'respond_support_chats',
                    'view_faqs', 'create_faqs', 'edit_faqs',
                    'view_activity_logs',
                    'send_notifications', 'view_notifications',
                ],
            ],
            
            // Admin Operations - Gestion coursiers et zones
            'operations' => [
                'description' => 'Opérations - Gère les coursiers, zones et logistique',
                'permissions' => [
                    'view_orders', 'assign_orders', 'edit_orders',
                    'view_couriers', 'create_couriers', 'edit_couriers', 'verify_couriers',
                    'view_withdrawals', 'approve_withdrawals', 'reject_withdrawals',
                    'view_zones', 'create_zones', 'edit_zones',
                    'view_traffic_incidents', 'manage_traffic_incidents',
                    'view_reports',
                    'send_notifications', 'view_notifications',
                ],
            ],
            
            // Admin Finance - Gestion financière
            'finance' => [
                'description' => 'Finance - Gère les paiements, retraits et codes promo',
                'permissions' => [
                    'view_orders',
                    'view_users',
                    'view_couriers',
                    'view_payments', 'refund_payments',
                    'view_withdrawals', 'approve_withdrawals', 'reject_withdrawals',
                    'view_promo_codes', 'create_promo_codes', 'edit_promo_codes', 'delete_promo_codes',
                    'view_reports', 'export_reports',
                ],
            ],
            
            // Admin Marketing - Gestion promotions et notifications
            'marketing' => [
                'description' => 'Marketing - Gère les promotions et communications',
                'permissions' => [
                    'view_orders',
                    'view_users',
                    'view_promo_codes', 'create_promo_codes', 'edit_promo_codes',
                    'view_faqs', 'create_faqs', 'edit_faqs', 'delete_faqs',
                    'send_notifications', 'view_notifications',
                    'view_reports',
                ],
            ],
            
            // Viewer - Lecture seule
            'viewer' => [
                'description' => 'Observateur - Accès en lecture seule',
                'permissions' => [
                    'view_orders',
                    'view_users',
                    'view_couriers',
                    'view_payments',
                    'view_withdrawals',
                    'view_zones',
                    'view_promo_codes',
                    'view_ratings',
                    'view_complaints',
                    'view_faqs',
                    'view_reports',
                    'view_notifications',
                ],
            ],
        ];

        $this->command->newLine();
        $this->command->info('📋 Création des rôles...');
        
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['name' => $roleName, 'guard_name' => 'web']
            );
            
            // Sync permissions (supprime et réajoute)
            $role->syncPermissions($roleData['permissions']);
            
            $this->command->info("  ✓ Rôle '{$roleName}' - {$roleData['description']}");
        }

        // ==================== ASSIGN ROLES TO EXISTING ADMINS ====================
        
        $this->command->newLine();
        $this->command->info('👥 Attribution des rôles aux administrateurs existants...');
        
        $admins = [
            'admin@ouagachap.bf' => 'super_admin',
            'support@ouagachap.bf' => 'support',
            'operations@ouagachap.bf' => 'operations',
            'finance@ouagachap.bf' => 'finance',
            'marketing@ouagachap.bf' => 'marketing',
        ];
        
        foreach ($admins as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
                $this->command->info("  ✓ Rôle '{$role}' assigné à {$email}");
            }
        }

        // ==================== SUMMARY ====================
        
        $this->command->newLine();
        $this->command->info('✅ Rôles et permissions créés avec succès!');
        $this->command->newLine();
        
        $this->command->table(
            ['Rôle', 'Description', 'Nb Permissions'],
            collect($roles)->map(fn ($data, $name) => [
                $name,
                $data['description'],
                count($data['permissions']),
            ])->toArray()
        );
        
        $this->command->newLine();
        $this->command->info('💡 Pour assigner un rôle à un admin:');
        $this->command->line('   php artisan tinker');
        $this->command->line('   User::find(1)->assignRole("super_admin")');
    }
}
