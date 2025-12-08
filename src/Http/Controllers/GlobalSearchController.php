<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Services\GlobalSearchService;

class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    /**
     * Perform global search across all resources.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'panel' => 'nullable|string',
            'resources' => 'nullable|array',
            'resources.*' => 'string',
        ]);

        $query = $request->input('q');
        $panel = $request->input('panel');
        $resources = $request->input('resources');

        $results = $this->searchService->search($query, $panel, $resources);

        // Store recent search for authenticated users
        if ($user = $request->user()) {
            $this->searchService->storeRecentSearch($user->id, $query);
        }

        return response()->json($results);
    }

    /**
     * Search within a specific resource type.
     */
    public function searchResource(Request $request, string $resource): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->searchResource($resource, $query, $limit);

        return response()->json([
            'resource' => $resource,
            'query' => $query,
            'results' => $results,
            'total' => count($results),
        ]);
    }

    /**
     * Get search suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $suggestions = $this->searchService->getSuggestions($userId);

        return response()->json($suggestions);
    }

    /**
     * Get list of searchable resources.
     */
    public function searchableResources(Request $request): JsonResponse
    {
        $panel = $request->input('panel');
        $resources = $this->searchService->getSearchableResources($panel);

        $list = [];
        foreach ($resources as $key => $resourceClass) {
            $list[] = [
                'key' => $key,
                'label' => $resourceClass::globalSearchResourceLabel(),
                'icon' => $resourceClass::globalSearchIcon(),
            ];
        }

        return response()->json([
            'resources' => $list,
        ]);
    }

    /**
     * Clear recent searches for current user.
     */
    public function clearRecent(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->searchService->clearRecentSearches($user->id);

        return response()->json([
            'message' => 'Recent searches cleared',
        ]);
    }
}
