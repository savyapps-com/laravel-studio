<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success response.
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = ['message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response.
     */
    protected function createdResponse(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = [], ?string $errorCode = null): JsonResponse
    {
        $response = ['message' => $message];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $resource = 'Resource', ?string $identifier = null): JsonResponse
    {
        $message = $identifier
            ? "{$resource} with identifier '{$identifier}' not found"
            : "{$resource} not found";

        return $this->errorResponse($message, 404, [], 'NOT_FOUND');
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->errorResponse($message, 401, [], 'UNAUTHORIZED');
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(string $message = 'You do not have permission to perform this action'): JsonResponse
    {
        return $this->errorResponse($message, 403, [], 'FORBIDDEN');
    }

    /**
     * Return a conflict response.
     */
    protected function conflictResponse(string $message = 'Resource conflict'): JsonResponse
    {
        return $this->errorResponse($message, 409, [], 'CONFLICT');
    }

    /**
     * Return a server error response.
     */
    protected function serverErrorResponse(string $message = 'An unexpected error occurred'): JsonResponse
    {
        return $this->errorResponse($message, 500, [], 'SERVER_ERROR');
    }

    /**
     * Return a paginated response.
     */
    protected function paginatedResponse(mixed $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Return a collection response with meta.
     */
    protected function collectionResponse(mixed $data, array $meta = [], string $message = 'Success'): JsonResponse
    {
        $response = [
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }
}
