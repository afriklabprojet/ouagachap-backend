<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Address\StoreSavedAddressRequest;
use App\Http\Requests\Address\UpdateSavedAddressRequest;
use App\Models\SavedAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedAddressController extends BaseController
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

        return $this->success($addresses);
    }

    /**
     * Create a new saved address
     *
     * @group Addresses
     * @authenticated
     */
    public function store(StoreSavedAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // Check limit (max 10 addresses per user)
        $count = SavedAddress::forUser($user->id)->count();
        if ($count >= 10) {
            return $this->error('Vous avez atteint la limite de 10 adresses sauvegardées.', 422);
        }

        // Check for duplicate address label
        $exists = SavedAddress::forUser($user->id)
            ->where('label', $validated['label'])
            ->exists();

        if ($exists) {
            return $this->error('Une adresse avec ce nom existe déjà.', 422);
        }

        $address = SavedAddress::create([
            'user_id' => $user->id,
            ...$validated,
            'is_default' => $validated['is_default'] ?? false,
            'type' => $validated['type'] ?? 'other',
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

        return $this->success($address, 'Adresse sauvegardée avec succès.', 201);
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
            return $this->notFound('Adresse non trouvée.');
        }

        return $this->success($address);
    }

    /**
     * Update a saved address
     *
     * @group Addresses
     * @authenticated
     */
    public function update(UpdateSavedAddressRequest $request, int $id): JsonResponse
    {
        $address = SavedAddress::forUser($request->user()->id)
            ->find($id);

        if (!$address) {
            return $this->notFound('Adresse non trouvée.');
        }

        $validated = $request->validated();

        // Check for duplicate label (if changing label)
        if (isset($validated['label']) && $validated['label'] !== $address->label) {
            $exists = SavedAddress::forUser($request->user()->id)
                ->where('label', $validated['label'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return $this->error('Une adresse avec ce nom existe déjà.', 422);
            }
        }

        $address->update($validated);

        // If this is set as default, unset others
        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return $this->success($address->fresh(), 'Adresse mise à jour avec succès.');
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
            return $this->notFound('Adresse non trouvée.');
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

        return $this->success(null, 'Adresse supprimée avec succès.');
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
            return $this->notFound('Adresse non trouvée.');
        }

        $address->setAsDefault();

        return $this->success($address->fresh(), 'Adresse définie par défaut.');
    }
}
