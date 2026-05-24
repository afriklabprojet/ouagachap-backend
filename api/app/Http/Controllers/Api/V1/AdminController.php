<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends BaseController
{
    private const MSG_USER_NOT_FOUND = 'Utilisateur non trouvé.';

    /**
     * Liste des administrateurs
     */
    public function index(Request $request): JsonResponse
    {
        $admins = User::where('role', UserRole::ADMIN)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginated($admins, 'Liste des administrateurs.');
    }

    /**
     * Créer un nouvel administrateur
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        return $this->success($admin, 'Administrateur créé avec succès.', 201);
    }

    /**
     * Voir un administrateur
     */
    public function show(User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return $this->notFound(self::MSG_USER_NOT_FOUND);
        }

        return $this->success($admin);
    }

    /**
     * Mettre à jour un administrateur
     */
    public function update(UpdateAdminRequest $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return $this->notFound(self::MSG_USER_NOT_FOUND);
        }

        $admin->update($request->validated());

        return $this->success($admin->fresh(), 'Administrateur mis à jour.');
    }

    /**
     * Changer le mot de passe d'un administrateur
     */
    public function changePassword(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return $this->notFound(self::MSG_USER_NOT_FOUND);
        }

        $validated = $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Révoquer tous les tokens pour forcer la re-connexion
        $admin->tokens()->delete();

        return $this->success(null, 'Mot de passe modifié avec succès.');
    }

    /**
     * Suspendre un administrateur
     */
    public function suspend(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return $this->notFound(self::MSG_USER_NOT_FOUND);
        }

        if ($admin->id === $request->user()->id) {
            return $this->error('Vous ne pouvez pas vous suspendre vous-même.', 422);
        }

        $admin->update(['status' => UserStatus::SUSPENDED]);

        // Révoquer tous les tokens du compte suspendu
        $admin->tokens()->delete();

        return $this->success(null, 'Administrateur suspendu.');
    }

    /**
     * Réactiver un administrateur
     */
    public function activate(User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return $this->notFound(self::MSG_USER_NOT_FOUND);
        }

        $admin->update(['status' => UserStatus::ACTIVE]);

        return $this->success(null, 'Administrateur réactivé.');
    }

    /**
     * Supprimer un administrateur (soft delete)
     */
    public function destroy(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return $this->notFound(self::MSG_USER_NOT_FOUND);
        }

        if ($admin->id === $request->user()->id) {
            return $this->error('Vous ne pouvez pas vous supprimer vous-même.', 422);
        }

        // Révoquer tous les tokens avant suppression
        $admin->tokens()->delete();
        $admin->delete();

        return $this->success(null, 'Administrateur supprimé.');
    }

    /**
     * Statistiques du dashboard admin
     * Cache de 5 minutes pour réduire la charge DB
     */
    public function dashboard(): JsonResponse
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'users' => DB::table('users')->whereNull('deleted_at')->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as clients,
                    SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as couriers,
                    SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as admins
                ", [UserRole::CLIENT->value, UserRole::COURIER->value, UserRole::ADMIN->value])->first(),
                'orders' => DB::table('orders')->whereNull('deleted_at')->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status IN (?, ?, ?, ?, ?) THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled
                ", [
                    OrderStatus::PENDING->value,
                    ...array_map(fn($s) => $s->value, OrderStatus::activeStatuses()),
                    OrderStatus::DELIVERED->value,
                    OrderStatus::CANCELLED->value,
                ])->first(),
                'payments' => DB::table('payments')->selectRaw("
                    COALESCE(SUM(amount), 0) as total,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                    COALESCE(SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END), 0) as completed
                ")->first(),
                'today' => [
                    'orders' => DB::table('orders')
                        ->whereNull('deleted_at')
                        ->whereDate('created_at', today())
                        ->count(),
                    'revenue' => DB::table('payments')
                        ->where('status', 'success')
                        ->whereDate('created_at', today())
                        ->sum('amount') ?? 0,
                    'new_users' => DB::table('users')
                        ->whereNull('deleted_at')
                        ->whereDate('created_at', today())
                        ->count(),
                ],
            ];
        });

        return $this->success($stats, 'Statistiques du dashboard.');
    }

    /**
     * Connexion admin via API (avec email/password)
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = User::where('email', $validated['email'])
            ->where('role', UserRole::ADMIN)
            ->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return $this->error('Identifiants incorrects.', 401);
        }

        if ($admin->status === UserStatus::SUSPENDED) {
            return $this->forbidden('Votre compte est suspendu.');
        }

        // Créer un token avec des abilities admin
        $token = $admin->createToken('admin-token', ['admin:*'])->plainTextToken;

        return $this->success([
            'user' => $admin,
            'token' => $token,
        ], 'Connexion réussie.');
    }
}
