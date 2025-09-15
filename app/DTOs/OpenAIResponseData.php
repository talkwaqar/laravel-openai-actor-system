<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;

/**
 * OpenAI Response Data Transfer Object
 * 
 * Type-safe container for OpenAI API responses.
 * Handles data extraction and validation from AI responses.
 */
class OpenAIResponseData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $address,
        public ?string $height = null,
        public ?string $weight = null,
        public ?string $gender = null,
        public ?int $age = null,
        public ?array $rawResponse = null,
        public ?string $model = null,
        public ?int $tokensUsed = null,
    ) {}

    /**
     * Create from OpenAI API response.
     */
    public static function fromApiResponse(array $response): self
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        $usage = $response['usage'] ?? [];
        
        // Parse the JSON response from OpenAI
        $extractedData = json_decode($content, true);
        
        if (!$extractedData) {
            throw new \InvalidArgumentException('Invalid JSON response from OpenAI');
        }

        return new self(
            firstName: $extractedData['first_name'] ?? '',
            lastName: $extractedData['last_name'] ?? '',
            address: $extractedData['address'] ?? '',
            height: $extractedData['height'] ?? null,
            weight: $extractedData['weight'] ?? null,
            gender: self::normalizeGender($extractedData['gender'] ?? null),
            age: isset($extractedData['age']) ? (int) $extractedData['age'] : null,
            rawResponse: $response,
            model: $response['model'] ?? null,
            tokensUsed: $usage['total_tokens'] ?? null,
        );
    }

    /**
     * Normalize gender values to match our enum.
     */
    private static function normalizeGender(?string $gender): ?string
    {
        if (!$gender) {
            return null;
        }

        $gender = strtolower(trim($gender));
        
        return match ($gender) {
            'male', 'm', 'man' => 'male',
            'female', 'f', 'woman' => 'female',
            'other', 'non-binary', 'nb' => 'other',
            'prefer not to say', 'prefer_not_to_say', 'unknown' => 'prefer_not_to_say',
            default => null,
        };
    }

    /**
     * Check if all required fields are present.
     */
    public function hasRequiredFields(): bool
    {
        return !empty($this->firstName) && 
               !empty($this->lastName) && 
               !empty($this->address);
    }

    /**
     * Get missing required fields.
     */
    public function getMissingRequiredFields(): array
    {
        $missing = [];

        if (empty($this->firstName)) {
            $missing[] = 'first_name';
        }

        if (empty($this->lastName)) {
            $missing[] = 'last_name';
        }

        if (empty($this->address)) {
            $missing[] = 'address';
        }

        return $missing;
    }

    /**
     * Get confidence score based on available data.
     */
    public function getConfidenceScore(): float
    {
        $score = 0.0;
        $maxScore = 7.0;

        // Required fields (higher weight)
        if (!empty($this->firstName)) $score += 2.0;
        if (!empty($this->lastName)) $score += 2.0;
        if (!empty($this->address)) $score += 2.0;

        // Optional fields (lower weight)
        if (!empty($this->height)) $score += 0.25;
        if (!empty($this->weight)) $score += 0.25;
        if (!empty($this->gender)) $score += 0.25;
        if (!empty($this->age)) $score += 0.25;

        return round($score / $maxScore, 2);
    }

    /**
     * Convert to array for storage.
     */
    public function toStorageArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address' => $this->address,
            'height' => $this->height,
            'weight' => $this->weight,
            'gender' => $this->gender,
            'age' => $this->age,
            'confidence_score' => $this->getConfidenceScore(),
            'tokens_used' => $this->tokensUsed,
            'model' => $this->model,
            'extracted_at' => now()->toISOString(),
        ];
    }
}
