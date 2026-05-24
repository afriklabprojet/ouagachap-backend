<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Zone;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends BaseController
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {}

    /**
     * Get all active zones
     * GET /api/v1/zones
     */
    public function index(): JsonResponse
    {
        $zones = $this->cacheService->getActiveZones();

        return $this->success($zones, 'Zones de livraison.');
    }

    /**
     * Get zone details
     * GET /api/v1/zones/{zone}
     */
    public function show(Zone $zone): JsonResponse
    {
        return $this->success($zone);
    }
}
