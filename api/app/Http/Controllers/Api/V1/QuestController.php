<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestController extends BaseController
{
    public function __construct(
        private readonly GamificationService $gamificationService,
    ) {}

    /**
     * List all active quests with the authenticated courier's progress.
     *
     * GET /api/v1/quests
     * Auth: Sanctum (courier)
     */
    public function index(Request $request): JsonResponse
    {
        $quests = $this->gamificationService->getCourierQuestsWithProgress($request->user());

        return $this->success([
            'quests' => $quests,
        ]);
    }
}
