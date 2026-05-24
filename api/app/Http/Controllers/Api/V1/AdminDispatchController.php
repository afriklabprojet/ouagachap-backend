<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Services\DelayPredictorService;
use App\Services\SmartDispatcherService;
use Illuminate\Http\JsonResponse;

/**
 * @group Dispatch Admin
 *
 * Endpoints d'administration pour l'assignation intelligente et la prédiction d'ETA.
 */
class AdminDispatchController extends BaseController
{
    public function __construct(
        private SmartDispatcherService $smartDispatcher,
        private DelayPredictorService  $delayPredictor,
    ) {}

    /**
     * Assigner automatiquement un coursier
     *
     * Déclenche l'assignation intelligente pour une commande en attente.
     * L'algorithme sélectionne le meilleur coursier disponible selon le score
     * composite (distance, charge, météo, trafic, batterie…).
     *
     * @urlParam order string required UUID de la commande. Example: 550e8400-e29b-41d4-a716-446655440000
     * @response 200 {"success":true,"message":"Coursier assigné avec succès.","data":{"order_id":"…","courier_id":"…","score":87.3}}
     * @response 422 {"success":false,"message":"Aucun coursier disponible."}
     */
    public function smartDispatch(Order $order): JsonResponse
    {
        $result = $this->smartDispatcher->dispatchOrder($order);

        if (!$result['success']) {
            return $this->error($result['message'] ?? 'Assignation impossible.', 422);
        }

        return $this->success($result, $result['message'] ?? 'Coursier assigné avec succès.');
    }

    /**
     * Suggestions d'assignation
     *
     * Retourne les 5 meilleurs coursiers candidats avec leur score détaillé,
     * sans déclencher d'assignation réelle.
     *
     * @urlParam order string required UUID de la commande. Example: 550e8400-e29b-41d4-a716-446655440000
     * @response 200 {"success":true,"message":"Suggestions de dispatch.","data":{"candidates":[…],"context":{…}}}
     */
    public function dispatchSuggestions(Order $order): JsonResponse
    {
        $suggestions = $this->smartDispatcher->getDispatchSuggestions($order);

        return $this->success($suggestions, 'Suggestions de dispatch.');
    }

    /**
     * Contexte d'assignation
     *
     * Retourne les informations environnementales (météo, trafic, heure de pointe)
     * influençant l'assignation pour cette commande.
     *
     * @urlParam order string required UUID de la commande. Example: 550e8400-e29b-41d4-a716-446655440000
     * @response 200 {"success":true,"message":"Contexte de dispatch.","data":{"weather":{…},"traffic":{…},"peak_hour":false}}
     */
    public function dispatchContext(Order $order): JsonResponse
    {
        $context = $this->smartDispatcher->getDispatchContext($order);

        return $this->success($context, 'Contexte de dispatch.');
    }

    /**
     * Prédiction ETA
     *
     * Retourne une estimation de l'heure d'arrivée (ETA) pour une commande
     * en prenant en compte les conditions météo et de trafic actuelles.
     *
     * @urlParam order string required UUID de la commande. Example: 550e8400-e29b-41d4-a716-446655440000
     * @response 200 {"success":true,"message":"ETA prédit.","data":{"minutes":22,"optimistic":17,"pessimistic":30,"confidence":74,"breakdown":{…},"factors":[…]}}
     */
    public function getEta(Order $order): JsonResponse
    {
        // Charger le coursier assigné si la commande en a un
        $courier = $order->courier_id
            ? \App\Models\User::find($order->courier_id)
            : null;

        $eta = $this->delayPredictor->predictETA($order, $courier);

        return $this->success($eta, 'ETA prédit.');
    }

    /**
     * Dispatch automatique des commandes en attente
     *
     * Force le traitement immédiat de toutes les commandes PENDING sans coursier.
     * Normalement déclenché toutes les 2 minutes par le scheduler.
     *
     * @response 200 {"success":true,"message":"Dispatch automatique terminé.","data":{"dispatched":3,"failed":1,"skipped":2}}
     */
    public function autoDispatch(): JsonResponse
    {
        $result = $this->smartDispatcher->autoDispatchPending();

        return $this->success($result, 'Dispatch automatique terminé.');
    }
}
