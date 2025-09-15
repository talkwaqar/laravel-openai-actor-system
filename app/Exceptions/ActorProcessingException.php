<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Actor Processing Exception
 *
 * Thrown when actor processing fails due to business logic violations
 * or data validation errors.
 */
class ActorProcessingException extends Exception
{
    protected array $errors = [];
    protected ?string $actorEmail = null;

    public function __construct(
        string $message = 'Actor processing failed',
        array $errors = [],
        ?string $actorEmail = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
        $this->actorEmail = $actorEmail;
    }

    /**
     * Create exception for duplicate email.
     */
    public static function duplicateEmail(string $email): self
    {
        return new self(
            message: "Actor with email '{$email}' already exists",
            errors: ['email' => 'This email has already been used'],
            actorEmail: $email,
            code: 409
        );
    }

    /**
     * Create exception for missing required fields.
     */
    public static function missingRequiredFields(array $missingFields, ?string $email = null): self
    {
        $fieldsList = implode(', ', $missingFields);

        return new self(
            message: "Missing required fields: {$fieldsList}",
            errors: ['required_fields' => $missingFields],
            actorEmail: $email,
            code: 422
        );
    }

    /**
     * Create exception for validation errors.
     */
    public static function validationFailed(array $errors, ?string $email = null): self
    {
        return new self(
            message: 'Actor data validation failed',
            errors: $errors,
            actorEmail: $email,
            code: 422
        );
    }

    /**
     * Create exception for processing timeout.
     */
    public static function processingTimeout(?string $email = null): self
    {
        return new self(
            message: 'Actor processing timed out',
            errors: ['timeout' => 'Processing took too long and was cancelled'],
            actorEmail: $email,
            code: 408
        );
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get actor email if available.
     */
    public function getActorEmail(): ?string
    {
        return $this->actorEmail;
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'error' => 'actor_processing_failed',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'actor_email' => $this->actorEmail,
            'code' => $this->getCode(),
        ];
    }
}
