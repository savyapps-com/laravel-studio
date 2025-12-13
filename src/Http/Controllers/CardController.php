<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use SavyApps\LaravelStudio\Services\CardService;
use SavyApps\LaravelStudio\Traits\ApiResponse;

class CardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CardService $cardService
    ) {}

    /**
     * Get cards for a resource.
     */
    public function resourceCards(Request $request, string $resource): JsonResponse
    {
        $cards = $this->cardService->getResourceCards($resource);

        return $this->collectionResponse($cards, [
            'resource' => $resource,
            'total' => count($cards),
        ]);
    }

    /**
     * Get dashboard cards for a panel.
     */
    public function dashboardCards(Request $request): JsonResponse
    {
        $panel = $request->input('panel');
        $cards = $this->cardService->getDashboardCards($panel);

        return response()->json([
            'data' => $cards,
            'meta' => [
                'panel' => $panel,
                'total' => count($cards),
            ],
        ]);
    }

    /**
     * Get a single card's data.
     */
    public function show(Request $request, string $resource, string $cardKey): JsonResponse
    {
        $card = $this->cardService->getCardData($resource, $cardKey);

        if (!$card) {
            return $this->notFoundResponse('Card', $cardKey);
        }

        return $this->successResponse($card);
    }

    /**
     * Refresh a card's data (bypasses cache).
     */
    public function refresh(Request $request, string $resource, string $cardKey): JsonResponse
    {
        $card = $this->cardService->refreshCard($resource, $cardKey);

        if (!$card) {
            return $this->notFoundResponse('Card', $cardKey);
        }

        return $this->successResponse($card, 'Card refreshed');
    }

    /**
     * Get available card types.
     */
    public function types(): JsonResponse
    {
        $types = $this->cardService->getCardTypes();

        return $this->successResponse(['types' => $types]);
    }

    /**
     * Clear card cache for a resource.
     */
    public function clearCache(Request $request, string $resource): JsonResponse
    {
        $this->cardService->clearResourceCardCache($resource);

        return $this->successResponse(['resource' => $resource], 'Card cache cleared');
    }

    /**
     * Clear all card caches.
     */
    public function clearAllCaches(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $superAdminRole = config('studio.authorization.super_admin_role', 'super_admin');
        $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole($superAdminRole);

        if (!$isSuperAdmin && !Gate::allows('manage-cards')) {
            return $this->forbiddenResponse('Unauthorized to clear all card caches');
        }

        $this->cardService->clearAllCardCaches();

        return $this->successResponse(null, 'All card caches cleared');
    }
}
