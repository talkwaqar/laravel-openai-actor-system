<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\In;

/**
 * Actor Data Transfer Object
 *
 * Type-safe data container for actor information.
 * Used for creating and updating actor records.
 */
class ActorData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public string $email,

        #[Required, Max(100)]
        public string $firstName,

        #[Required, Max(100)]
        public string $lastName,

        #[Required, Max(500)]
        public string $address,

        #[Max(50)]
        public ?string $height = null,

        #[Max(50)]
        public ?string $weight = null,

        #[In(['male', 'female', 'other', 'prefer_not_to_say'])]
        public ?string $gender = null,

        public ?int $age = null,

        #[Required]
        public string $originalDescription = '',

        public ?array $openaiResponse = null,
    ) {}

    /**
     * Create from OpenAI response.
     */
    public static function fromOpenAIResponse(
        string $email,
        string $originalDescription,
        OpenAIResponseData $openaiData
    ): self {
        return new self(
            email: $email,
            firstName: $openaiData->firstName,
            lastName: $openaiData->lastName,
            address: $openaiData->address,
            height: $openaiData->height,
            weight: $openaiData->weight,
            gender: $openaiData->gender,
            age: $openaiData->age,
            originalDescription: $originalDescription,
            openaiResponse: $openaiData->toArray(),
        );
    }

    /**
     * Create from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            address: $data['address'],
            height: $data['height'] ?? null,
            weight: $data['weight'] ?? null,
            gender: $data['gender'] ?? null,
            age: $data['age'] ?? null,
            originalDescription: $data['original_description'] ?? '',
            openaiResponse: $data['openai_response'] ?? null,
        );
    }

    /**
     * Convert to array for model creation/update.
     */
    public function toModelArray(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address' => $this->address,
            'height' => $this->height,
            'weight' => $this->weight,
            'gender' => $this->gender,
            'age' => $this->age,
            'original_description' => $this->originalDescription,
            'openai_response' => $this->openaiResponse,
        ];
    }

    /**
     * Check if required fields are present.
     */
    public function hasRequiredFields(): bool
    {
        return !empty($this->firstName) &&
               !empty($this->lastName) &&
               !empty($this->address);
    }

    /**
     * Get validation errors for required fields.
     */
    public function getRequiredFieldsErrors(): array
    {
        $errors = [];

        if (empty($this->firstName)) {
            $errors[] = 'First name is required';
        }

        if (empty($this->lastName)) {
            $errors[] = 'Last name is required';
        }

        if (empty($this->address)) {
            $errors[] = 'Address is required';
        }

        return $errors;
    }
}
