<?php

namespace SavyApps\LaravelStudio\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid permission name is used.
 *
 * This exception is thrown when a permission name is used that doesn't
 * exist in the Permission enum. This helps catch typos and ensures
 * permission names are always valid.
 *
 * @example
 * use SavyApps\LaravelStudio\Exceptions\InvalidPermissionException;
 *
 * try {
 *     $user->hasPermission('users.craete'); // Typo!
 * } catch (InvalidPermissionException $e) {
 *     // Handle invalid permission
 * }
 */
class InvalidPermissionException extends Exception
{
    /**
     * The invalid permission name.
     *
     * @var string
     */
    protected string $permission;

    /**
     * Create a new exception instance.
     *
     * @param string $permission The invalid permission name
     * @param string|null $message Custom error message
     */
    public function __construct(string $permission, ?string $message = null)
    {
        $this->permission = $permission;

        $message = $message ?? "Invalid permission: '{$permission}'. Please use a valid permission constant from the Permission enum.";

        parent::__construct($message);
    }

    /**
     * Get the invalid permission name.
     *
     * @return string
     */
    public function getPermission(): string
    {
        return $this->permission;
    }

    /**
     * Create an exception for an unknown permission.
     *
     * @param string $permission The unknown permission name
     * @return static
     */
    public static function unknownPermission(string $permission): static
    {
        return new static(
            $permission,
            "Unknown permission: '{$permission}'. Check the Permission enum for available permissions."
        );
    }

    /**
     * Create an exception for a malformed permission name.
     *
     * @param string $permission The malformed permission name
     * @return static
     */
    public static function malformedPermission(string $permission): static
    {
        return new static(
            $permission,
            "Malformed permission name: '{$permission}'. Permission names should follow the format 'resource.action'."
        );
    }
}
