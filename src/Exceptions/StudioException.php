<?php

namespace SavyApps\LaravelStudio\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class StudioException extends Exception
{
    protected int $statusCode;

    protected array $errors;

    protected ?string $errorCode;

    /**
     * Suggested solution for this error.
     */
    protected string $solution = '';

    /**
     * Documentation link for more information.
     */
    protected string $docsLink = '';

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
     * Create exception with solution guidance.
     */
    public static function withSolution(
        string $message,
        string $solution,
        int $statusCode = 500,
        string $errorCode = 'ERROR'
    ): static {
        $exception = new static($message, $statusCode, [], $errorCode);
        $exception->solution = $solution;

        return $exception;
    }

    /**
     * Create exception with documentation link.
     */
    public function withDocs(string $docsLink): static
    {
        $this->docsLink = $docsLink;

        return $this;
    }

    /**
     * Get the suggested solution.
     */
    public function getSolution(): string
    {
        return $this->solution;
    }

    /**
     * Get the documentation link.
     */
    public function getDocsLink(): string
    {
        return $this->docsLink;
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
     * Create a resource not registered exception.
     */
    public static function resourceNotRegistered(string $resource): static
    {
        return static::withSolution(
            "Resource '{$resource}' is not registered",
            "Add the resource to config/studio.php under 'resources' array:\n" .
            "  'resources' => [\n" .
            "      '{$resource}' => [\n" .
            "          'class' => \\App\\Resources\\" . ucfirst($resource) . "Resource::class,\n" .
            "      ],\n" .
            "  ],",
            404,
            'RESOURCE_NOT_REGISTERED'
        );
    }

    /**
     * Create a resource not in panel exception.
     */
    public static function resourceNotInPanel(string $resource, string $panel): static
    {
        return static::withSolution(
            "Resource '{$resource}' is not available in panel '{$panel}'",
            "Add the resource to the panel's resources array in config/studio.php:\n" .
            "  'panels' => [\n" .
            "      '{$panel}' => [\n" .
            "          'resources' => ['{$resource}', ...],\n" .
            "      ],\n" .
            "  ],",
            404,
            'RESOURCE_NOT_IN_PANEL'
        );
    }

    /**
     * Create a panel not found exception.
     */
    public static function panelNotFound(string $panel): static
    {
        return static::withSolution(
            "Panel '{$panel}' not found",
            "Define the panel in config/studio.php:\n" .
            "  'panels' => [\n" .
            "      '{$panel}' => [\n" .
            "          'name' => '" . ucfirst($panel) . " Panel',\n" .
            "          'roles' => ['admin'],\n" .
            "          'resources' => [...],\n" .
            "      ],\n" .
            "  ],",
            404,
            'PANEL_NOT_FOUND'
        );
    }

    /**
     * Create a model not found exception with helpful message.
     */
    public static function modelNotFound(string $model): static
    {
        $shortName = class_basename($model);

        return static::withSolution(
            "Model '{$shortName}' not found",
            "Ensure the model exists at app/Models/{$shortName}.php\n" .
            "If you haven't run the installer yet: php artisan studio:install --all",
            404,
            'MODEL_NOT_FOUND'
        );
    }

    /**
     * Create a missing trait exception.
     */
    public static function missingTrait(string $model, string $trait): static
    {
        $shortModel = class_basename($model);
        $shortTrait = class_basename($trait);

        return static::withSolution(
            "Model '{$shortModel}' is missing required trait '{$shortTrait}'",
            "Add the trait to your model:\n" .
            "  use {$trait};\n\n" .
            "  class {$shortModel} extends Model\n" .
            "  {\n" .
            "      use {$shortTrait};\n" .
            "      ...\n" .
            "  }",
            500,
            'MISSING_TRAIT'
        );
    }

    /**
     * Create a permission denied exception with helpful message.
     */
    public static function permissionDenied(string $permission, ?string $resource = null): static
    {
        $message = $resource
            ? "You don't have permission to '{$permission}' on resource '{$resource}'"
            : "You don't have permission to '{$permission}'";

        return static::withSolution(
            $message,
            "Ensure your user has the required permission.\n" .
            "Run: php artisan studio:sync-permissions\n" .
            "Then assign the permission to the user's role.",
            403,
            'PERMISSION_DENIED'
        );
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

        // Include solution in debug mode for better DX
        if (config('app.debug') && $this->solution) {
            $response['solution'] = $this->solution;
        }

        if (config('app.debug') && $this->docsLink) {
            $response['docs'] = $this->docsLink;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Get formatted message for console output.
     */
    public function getConsoleMessage(): string
    {
        $output = "[Laravel Studio] {$this->getMessage()}";

        if ($this->solution) {
            $output .= "\n\nTo fix:\n{$this->solution}";
        }

        if ($this->docsLink) {
            $output .= "\n\nDocs: {$this->docsLink}";
        }

        return $output;
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
