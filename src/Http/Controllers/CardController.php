<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Services\CardService;

class CardController extends Controller
{
    public function __construct(
        protected CardService $cardService
    ) {}

    /**
     * Get cards for a resource.
     */
    public function resourceCards(Request $request, string $resource): JsonResponse
    {
        $cards = $this->cardService->getResourceCards($resource);

        return response()->json([
            'resource' => $resource,
            'cards' => $cards,
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
            'panel' => $panel,
            'cards' => $cards,
            'total' => count($cards),
        ]);
    }

    /**
     * Get a single card's data.
     */
    public function show(Request $request, string $resource, string $cardKey): JsonResponse
    {
        $card = $this->cardService->getCardData($resource, $cardKey);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found',
            ], 404);
        }

        return response()->json($card);
    }

    /**
     * Refresh a card's data (bypasses cache).
     */
    public function refresh(Request $request, string $resource, string $cardKey): JsonResponse
    {
        $card = $this->cardService->refreshCard($resource, $cardKey);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found',
            ], 404);
        }

        return response()->json($card);
    }

    /**
     * Get available card types.
     */
    public function types(): JsonResponse
    {
        $types = $this->cardService->getCardTypes();

        return response()->json([
            'types' => $types,
        ]);
    }

    /**
     * Clear card cache for a resource.
     */
    public function clearCache(Request $request, string $resource): JsonResponse
    {
        $this->cardService->clearResourceCardCache($resource);

        return response()->json([
            'message' => 'Card cache cleared',
            'resource' => $resource,
        ]);
    }

    /**
     * Clear all card caches.
     */
    public function clearAllCaches(): JsonResponse
    {
        $this->cardService->clearAllCardCaches();

        return response()->json([
            'message' => 'All card caches cleared',
        ]);
    }
}
