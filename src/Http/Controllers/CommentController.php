<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Models\Comment;
use SavyApps\LaravelStudio\Services\CommentService;

class CommentController extends Controller
{
    /**
     * Commentable model mappings - can be extended via config
     */
    protected array $commentableModels = [];

    public function __construct(
        protected CommentService $commentService
    ) {
        $this->commentableModels = config('studio.comments.models', []);
    }

    /**
     * Display a listing of comments for a commentable model
     */
    public function index(Request $request, string $commentableType, int $commentableId): JsonResponse
    {
        // Resolve the model class
        $modelClass = $this->resolveModelClass($commentableType);
        if (! $modelClass) {
            return response()->json([
                'message' => 'Invalid commentable type',
            ], 400);
        }

        $model = $modelClass::findOrFail($commentableId);

        // Check access based on model type
        if (! $this->hasAccess($request->user(), $model)) {
            return response()->json([
                'message' => 'You do not have access to view these comments',
            ], 403);
        }

        // Use CommentService to get comments with cached user data
        $comments = $this->commentService->getComments($model);

        return response()->json([
            'data' => $comments,
        ]);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, string $commentableType, int $commentableId): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        // Resolve the model class
        $modelClass = $this->resolveModelClass($commentableType);
        if (! $modelClass) {
            return response()->json([
                'message' => 'Invalid commentable type',
            ], 400);
        }

        $model = $modelClass::findOrFail($commentableId);

        // Check access based on model type
        if (! $this->hasAccess($request->user(), $model)) {
            return response()->json([
                'message' => 'You do not have access to comment on this item',
            ], 403);
        }

        $comment = $this->commentService->createComment(
            $model,
            $validated['comment'],
            $request->user()->id,
            $validated['parent_id'] ?? null
        );

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment,
        ], 201);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        // Simple authorization - users can only edit their own comments
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only edit your own comments',
            ], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $comment = $this->commentService->updateComment($comment, $validated['comment']);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => $comment,
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        // Simple authorization - users can only delete their own comments
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only delete your own comments',
            ], 403);
        }

        $comment->delete();

        return response()->json([], 204);
    }

    /**
     * Resolve the model class from the commentable type
     */
    protected function resolveModelClass(string $commentableType): ?string
    {
        return $this->commentableModels[$commentableType] ?? null;
    }

    /**
     * Check if user has access to the model
     * Override this method for custom access control
     */
    protected function hasAccess($user, $model): bool
    {
        // Default: allow all authenticated users
        // Override in extending controller for custom access control
        return true;
    }
}
