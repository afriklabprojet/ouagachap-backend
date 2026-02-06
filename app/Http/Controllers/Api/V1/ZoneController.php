<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends BaseController
{
    /**
     * Get all active zones
     * GET /api/v1/zones
     */
    public function index(): JsonResponse
    {
        $zones = Zone::active()->get();

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
