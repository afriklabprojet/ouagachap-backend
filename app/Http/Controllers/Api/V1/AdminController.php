<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
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

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * Créer un nouvel administrateur
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|digits:8|unique:users,phone',
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Administrateur créé avec succès.',
            'data' => $admin,
        ], 201);
    }

    /**
     * Voir un administrateur
     */
    public function show(User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $admin,
        ]);
    }

    /**
     * Mettre à jour un administrateur
     */
    public function update(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($admin->id)],
            'phone' => ['sometimes', 'digits:8', Rule::unique('users')->ignore($admin->id)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
        ]);

        $admin->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Administrateur mis à jour.',
            'data' => $admin->fresh(),
        ]);
    }

    /**
     * Changer le mot de passe d'un administrateur
     */
    public function changePassword(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        $validated = $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => 'required|same:password',
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }

    /**
     * Suspendre un administrateur
     */
    public function suspend(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Ne pas permettre de se suspendre soi-même
        if ($admin->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous suspendre vous-même.',
            ], 422);
        }

        $admin->update(['status' => UserStatus::SUSPENDED]);

        return response()->json([
            'success' => true,
            'message' => 'Administrateur suspendu.',
        ]);
    }

    /**
     * Réactiver un administrateur
     */
    public function activate(User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        $admin->update(['status' => UserStatus::ACTIVE]);

        return response()->json([
            'success' => true,
            'message' => 'Administrateur réactivé.',
        ]);
    }

    /**
     * Supprimer un administrateur (soft delete)
     */
    public function destroy(Request $request, User $admin): JsonResponse
    {
        if ($admin->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Ne pas permettre de se supprimer soi-même
        if ($admin->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous supprimer vous-même.',
            ], 422);
        }

        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Administrateur supprimé.',
        ]);
    }

    /**
     * Statistiques du dashboard admin
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'clients' => User::where('role', UserRole::CLIENT)->count(),
                'couriers' => User::where('role', UserRole::COURIER)->count(),
                'admins' => User::where('role', UserRole::ADMIN)->count(),
            ],
            'orders' => [
                'total' => \App\Models\Order::count(),
                'pending' => \App\Models\Order::where('status', 'pending')->count(),
                'in_progress' => \App\Models\Order::whereIn('status', ['assigned', 'picked_up'])->count(),
                'delivered' => \App\Models\Order::where('status', 'delivered')->count(),
                'cancelled' => \App\Models\Order::where('status', 'cancelled')->count(),
            ],
            'payments' => [
                'total' => \App\Models\Payment::sum('amount'),
                'pending' => \App\Models\Payment::where('status', 'pending')->sum('amount'),
                'completed' => \App\Models\Payment::where('status', 'completed')->sum('amount'),
            ],
            'today' => [
                'orders' => \App\Models\Order::whereDate('created_at', today())->count(),
                'revenue' => \App\Models\Payment::where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->sum('amount'),
                'new_users' => User::whereDate('created_at', today())->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Connexion admin via API (avec email/password)
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = User::where('email', $validated['email'])
            ->where('role', UserRole::ADMIN)
            ->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        if ($admin->status === UserStatus::SUSPENDED) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est suspendu.',
            ], 403);
        }

        // Créer un token avec des abilities admin
        $token = $admin->createToken('admin-token', ['admin:*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'user' => $admin,
                'token' => $token,
            ],
        ]);
    }
}
