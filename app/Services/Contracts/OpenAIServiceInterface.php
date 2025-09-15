<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\OpenAIResponseData;

/**
 * OpenAI Service Interface
 * 
 * Defines the contract for OpenAI API integration.
 * Implements circuit breaker pattern and retry logic for resilient API calls.
 */
interface OpenAIServiceInterface
{
    /**
     * Extract actor information from description.
     * 
     * @param string $description
     * @return OpenAIResponseData
     * @throws \App\Exceptions\OpenAIProcessingException
     */
    public function extractActorInformation(string $description): OpenAIResponseData;

    /**
     * Validate extracted actor information.
     * 
     * @param OpenAIResponseData $responseData
     * @return bool
     */
    public function validateExtractedInformation(OpenAIResponseData $responseData): bool;

    /**
     * Get API usage statistics.
     * 
     * @return array
     */
    public function getApiUsageStats(): array;

    /**
     * Check API health status.
     * 
     * @return bool
     */
    public function isApiHealthy(): bool;

    /**
     * Get rate limit information.
     * 
     * @return array
     */
    public function getRateLimitInfo(): array;
}
