<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedAddressController extends Controller
{
    /**
     * Get all saved addresses for the authenticated user
     * 
     * @group Addresses
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = SavedAddress::forUser($request->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    /**
     * Create a new saved address
     * 
     * @group Addresses
     * @authenticated
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'instructions' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'type' => 'in:home,work,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check limit (max 10 addresses per user)
        $count = SavedAddress::forUser($user->id)->count();
        if ($count >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez atteint la limite de 10 adresses sauvegardées',
            ], 422);
        }

        // Check for duplicate address label
        $exists = SavedAddress::forUser($user->id)
            ->where('label', $request->label)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Une adresse avec ce nom existe déjà',
            ], 422);
        }

        $address = SavedAddress::create([
            'user_id' => $user->id,
            'label' => $request->label,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'contact_name' => $request->contact_name,
            'contact_phone' => $request->contact_phone,
            'instructions' => $request->instructions,
            'is_default' => $request->is_default ?? false,
            'type' => $request->type ?? 'other',
        ]);

        // If this is set as default, unset others
        if ($address->is_default) {
            $address->setAsDefault();
        }

        // If this is the first address, set it as default
        if ($count === 0) {
            $address->setAsDefault();
            $address->refresh();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse sauvegardée avec succès',
            'data' => $address,
        ], 201);
    }

    /**
     * Get a specific saved address
     * 
     * @group Addresses
     * @authenticated
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $address = SavedAddress::forUser($request->user()->id)
            ->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $address,
        ]);
    }

    /**
     * Update a saved address
     * 
     * @group Addresses
     * @authenticated
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $address = SavedAddress::forUser($request->user()->id)
            ->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|required|string|max:50',
            'address' => 'sometimes|required|string|max:255',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'instructions' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'type' => 'in:home,work,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicate label (if changing label)
        if ($request->has('label') && $request->label !== $address->label) {
            $exists = SavedAddress::forUser($request->user()->id)
                ->where('label', $request->label)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une adresse avec ce nom existe déjà',
                ], 422);
            }
        }

        $address->update($request->only([
            'label',
            'address',
            'latitude',
            'longitude',
            'contact_name',
            'contact_phone',
            'instructions',
            'is_default',
            'type',
        ]));

        // If this is set as default, unset others
        if ($request->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse mise à jour avec succès',
            'data' => $address->fresh(),
        ]);
    }

    /**
     * Delete a saved address
     * 
     * @group Addresses
     * @authenticated
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = SavedAddress::forUser($request->user()->id)
            ->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse non trouvée',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If deleted address was default, set another one as default
        if ($wasDefault) {
            $newDefault = SavedAddress::forUser($request->user()->id)
                ->first();
            
            if ($newDefault) {
                $newDefault->setAsDefault();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse supprimée avec succès',
        ]);
    }

    /**
     * Set an address as default
     * 
     * @group Addresses
     * @authenticated
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $address = SavedAddress::forUser($request->user()->id)
            ->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse non trouvée',
            ], 404);
        }

        $address->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Adresse définie par défaut',
            'data' => $address->fresh(),
        ]);
    }
}
