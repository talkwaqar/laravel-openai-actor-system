<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Max;

/**
 * Actor Submission Data Transfer Object
 *
 * Type-safe data container for actor submission requests.
 * Implements validation rules and data transformation.
 */
class ActorSubmissionData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public string $email,

        #[Required, Max(2000)]
        public string $description,

        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * Create from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            description: $data['description'],
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
        );
    }

    /**
     * Validate the submission data.
     */
    public function validateData(): array
    {
        $errors = [];

        // Email uniqueness validation (will be handled at service level)
        if (empty($this->email)) {
            $errors['email'] = 'Email is required.';
        }

        // Description validation
        if (empty($this->description)) {
            $errors['description'] = 'Description is required.';
        }

        if (strlen($this->description) < 10) {
            $errors['description'] = 'Description must be at least 10 characters long.';
        }

        return $errors;
    }

    /**
     * Check if the data is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validateData());
    }
}
