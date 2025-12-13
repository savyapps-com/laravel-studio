<?php

namespace SavyApps\LaravelStudio\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class StudioException extends Exception
{
    protected int $statusCode;

    protected array $errors;

    protected ?string $errorCode;

    public function __construct(
        string $message,
        int $statusCode = 500,
        array $errors = [],
        ?string $errorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->statusCode = $statusCode;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
    }

    /**
     * Create a validation exception.
     */
    public static function validation(array $errors, string $message = 'Validation failed'): static
    {
        return new static($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Create a not found exception.
     */
    public static function notFound(string $resource = 'Resource', ?string $identifier = null): static
    {
        $message = $identifier
            ? "{$resource} with identifier '{$identifier}' not found"
            : "{$resource} not found";

        return new static($message, 404, [], 'NOT_FOUND');
    }

    /**
     * Create an unauthorized exception.
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return new static($message, 401, [], 'UNAUTHORIZED');
    }

    /**
     * Create a forbidden exception.
     */
    public static function forbidden(string $message = 'You do not have permission to perform this action'): static
    {
        return new static($message, 403, [], 'FORBIDDEN');
    }

    /**
     * Create a conflict exception.
     */
    public static function conflict(string $message = 'Resource conflict'): static
    {
        return new static($message, 409, [], 'CONFLICT');
    }

    /**
     * Create a bad request exception.
     */
    public static function badRequest(string $message = 'Bad request'): static
    {
        return new static($message, 400, [], 'BAD_REQUEST');
    }

    /**
     * Create a server error exception.
     */
    public static function serverError(string $message = 'An unexpected error occurred', ?\Throwable $previous = null): static
    {
        return new static($message, 500, [], 'SERVER_ERROR', $previous);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Render the exception as a JSON response.
     */
    public function render(): JsonResponse
    {
        $response = [
            'message' => $this->getMessage(),
        ];

        if ($this->errorCode) {
            $response['error_code'] = $this->errorCode;
        }

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Report the exception.
     */
    public function report(): bool
    {
        // Only report server errors to logs
        return $this->statusCode >= 500;
    }
}
