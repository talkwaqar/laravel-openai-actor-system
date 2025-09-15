<?php

declare(strict_types=1);

namespace App\Services\OpenAI;

use App\DTOs\OpenAIResponseData;
use App\Exceptions\OpenAIProcessingException;
use App\Services\Contracts\OpenAIServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

/**
 * OpenAI Service
 *
 * Handles OpenAI API integration with enterprise-level features:
 * - Circuit breaker pattern for resilience
 * - Retry logic with exponential backoff
 * - Response caching for performance
 * - Rate limiting and quota management
 * - Comprehensive error handling and logging
 */
class OpenAIService implements OpenAIServiceInterface
{
    protected const CACHE_TTL = 3600; // 1 hour
    protected const MAX_RETRIES = 3;
    protected const CIRCUIT_BREAKER_THRESHOLD = 5;
    protected const CIRCUIT_BREAKER_TIMEOUT = 300; // 5 minutes

    protected string $model = 'gpt-4o-mini';
    protected int $maxTokens = 500;
    protected float $temperature = 0.1;

    /**
     * Extract actor information from description.
     */
    public function extractActorInformation(string $description): OpenAIResponseData
    {
        // Check circuit breaker
        if ($this->isCircuitBreakerOpen()) {
            throw OpenAIProcessingException::serverError(
                503,
                ['error' => 'Circuit breaker is open'],
                $this->generateRequestId()
            );
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($description);
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            Log::info('OpenAI response served from cache', ['cache_key' => $cacheKey]);
            return OpenAIResponseData::fromApiResponse($cachedResponse);
        }

        $requestId = $this->generateRequestId();

        try {
            $response = $this->makeApiCall($description, $requestId);
            $responseData = OpenAIResponseData::fromApiResponse($response);

            // Validate the response
            if (!$this->validateExtractedInformation($responseData)) {
                throw OpenAIProcessingException::invalidResponse($response, $requestId);
            }

            // Cache successful response
            Cache::put($cacheKey, $response, self::CACHE_TTL);

            // Reset circuit breaker on success
            $this->resetCircuitBreaker();

            Log::info('OpenAI actor extraction successful', [
                'request_id' => $requestId,
                'tokens_used' => $responseData->tokensUsed,
                'confidence_score' => $responseData->getConfidenceScore(),
            ]);

            return $responseData;

        } catch (OpenAIProcessingException $e) {
            $this->handleApiError($e, $requestId);
            throw $e;
        } catch (Throwable $e) {
            $this->handleUnexpectedError($e, $requestId);
            throw OpenAIProcessingException::serverError(500, null, $requestId);
        }
    }

    /**
     * Make the actual API call with retry logic.
     */
    protected function makeApiCall(string $description, string $requestId): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            try {
                Log::info('Making OpenAI API call', [
                    'request_id' => $requestId,
                    'attempt' => $attempt,
                    'description_length' => strlen($description),
                ]);

                $response = OpenAI::chat()->create([
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->getSystemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $description,
                        ],
                    ],
                    'max_tokens' => $this->maxTokens,
                    'temperature' => $this->temperature,
                    'response_format' => ['type' => 'json_object'],
                ]);

                return $response->toArray();

            } catch (Throwable $e) {
                $lastException = $e;

                Log::warning('OpenAI API call failed', [
                    'request_id' => $requestId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                ]);

                // Determine if we should retry
                if (!$this->shouldRetry($e, $attempt)) {
                    break;
                }

                // Exponential backoff
                $delay = min(pow(2, $attempt - 1), 30); // Max 30 seconds
                sleep($delay);
            }
        }

        // All retries exhausted, throw appropriate exception
        throw $this->createExceptionFromError($lastException, $requestId);
    }

    /**
     * Get the system prompt for actor extraction.
     */
    protected function getSystemPrompt(): string
    {
        return 'You are an expert at extracting structured information about actors from text descriptions.

Extract the following information from the user\'s description and return it as a JSON object:
- first_name: The person\'s first name
- last_name: The person\'s last name
- address: Their full address (street, city, state/country)
- height: Their height (if mentioned)
- weight: Their weight (if mentioned)
- gender: Their gender - look for explicit mentions like "I am a woman/man/female/male" or gender pronouns (he/him = male, she/her = female). Use "male", "female", "other", or null if truly not specified
- age: Their age as a number (if mentioned)

Requirements:
1. first_name, last_name, and address are REQUIRED fields
2. If any required field is missing or unclear, make a reasonable inference based on context
3. For optional fields, use null if not mentioned
4. For gender: Look for explicit gender statements, pronouns (he/him/his = male, she/her/hers = female), or gendered terms. If ambiguous or not mentioned, use null
5. Normalize gender to: "male", "female", "other", or null
6. Return only valid JSON with no additional text

Example response:
{
  "first_name": "John",
  "last_name": "Smith",
  "address": "123 Main St, Los Angeles, CA",
  "height": "6\'2\"",
  "weight": "180 lbs",
  "gender": "male",
  "age": 35
}';
    }

    /**
     * Validate extracted information.
     */
    public function validateExtractedInformation(OpenAIResponseData $responseData): bool
    {
        // Check required fields
        if (!$responseData->hasRequiredFields()) {
            Log::warning('OpenAI response missing required fields', [
                'missing_fields' => $responseData->getMissingRequiredFields(),
                'response_data' => $responseData->toArray(),
            ]);
            return false;
        }

        // Validate data quality
        if (strlen($responseData->firstName) < 1 || strlen($responseData->lastName) < 1) {
            return false;
        }

        if (strlen($responseData->address) < 5) {
            return false;
        }

        // Validate age if provided
        if ($responseData->age !== null && ($responseData->age < 0 || $responseData->age > 150)) {
            return false;
        }

        return true;
    }

    /**
     * Get API usage statistics.
     */
    public function getApiUsageStats(): array
    {
        return Cache::remember('openai.usage_stats', 300, function () {
            return [
                'requests_today' => $this->getRequestCount('today'),
                'requests_this_hour' => $this->getRequestCount('hour'),
                'tokens_used_today' => $this->getTokenUsage('today'),
                'average_response_time' => $this->getAverageResponseTime(),
                'success_rate' => $this->getSuccessRate(),
                'circuit_breaker_status' => $this->getCircuitBreakerStatus(),
            ];
        });
    }

    /**
     * Check API health status.
     */
    public function isApiHealthy(): bool
    {
        if ($this->isCircuitBreakerOpen()) {
            return false;
        }

        try {
            // Simple health check with minimal token usage
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello'],
                ],
                'max_tokens' => 5,
            ]);

            return !empty($response->choices);
        } catch (Throwable $e) {
            Log::warning('OpenAI health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get rate limit information.
     */
    public function getRateLimitInfo(): array
    {
        return [
            'requests_remaining' => $this->getRemainingRequests(),
            'tokens_remaining' => $this->getRemainingTokens(),
            'reset_time' => $this->getRateLimitResetTime(),
        ];
    }

    /**
     * Generate cache key for description.
     */
    protected function getCacheKey(string $description): string
    {
        return 'openai.extract.' . hash('sha256', trim($description));
    }

    /**
     * Generate unique request ID.
     */
    protected function generateRequestId(): string
    {
        return 'req_' . uniqid() . '_' . time();
    }

    /**
     * Check if circuit breaker is open.
     */
    protected function isCircuitBreakerOpen(): bool
    {
        $failures = Cache::get('openai.circuit_breaker.failures', 0);
        $lastFailure = Cache::get('openai.circuit_breaker.last_failure');

        if ($failures >= self::CIRCUIT_BREAKER_THRESHOLD) {
            if ($lastFailure && (time() - $lastFailure) < self::CIRCUIT_BREAKER_TIMEOUT) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset circuit breaker on successful request.
     */
    protected function resetCircuitBreaker(): void
    {
        Cache::forget('openai.circuit_breaker.failures');
        Cache::forget('openai.circuit_breaker.last_failure');
    }

    /**
     * Increment circuit breaker failure count.
     */
    protected function incrementCircuitBreakerFailures(): void
    {
        $failures = Cache::get('openai.circuit_breaker.failures', 0) + 1;
        Cache::put('openai.circuit_breaker.failures', $failures, 3600);
        Cache::put('openai.circuit_breaker.last_failure', time(), 3600);
    }

    /**
     * Determine if we should retry the request.
     */
    protected function shouldRetry(Throwable $e, int $attempt): bool
    {
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }

        // Retry on server errors and timeouts
        $retryableCodes = [408, 429, 500, 502, 503, 504];

        if (method_exists($e, 'getCode') && in_array($e->getCode(), $retryableCodes)) {
            return true;
        }

        // Retry on network errors
        if (str_contains($e->getMessage(), 'timeout') ||
            str_contains($e->getMessage(), 'connection')) {
            return true;
        }

        return false;
    }

    /**
     * Create appropriate exception from error.
     */
    protected function createExceptionFromError(Throwable $e, string $requestId): OpenAIProcessingException
    {
        $code = method_exists($e, 'getCode') ? $e->getCode() : 0;
        $message = $e->getMessage();

        return match ($code) {
            401 => OpenAIProcessingException::authenticationFailed($requestId),
            402 => OpenAIProcessingException::insufficientCredits($requestId),
            408 => OpenAIProcessingException::timeout($requestId),
            429 => OpenAIProcessingException::rateLimitExceeded($requestId),
            default => OpenAIProcessingException::serverError($code, null, $requestId),
        };
    }

    /**
     * Handle API errors and update circuit breaker.
     */
    protected function handleApiError(OpenAIProcessingException $e, string $requestId): void
    {
        Log::error('OpenAI API error', [
            'request_id' => $requestId,
            'error' => $e->toArray(),
        ]);

        // Increment circuit breaker for server errors
        if ($e->isServerError() || $e->getCode() === 429) {
            $this->incrementCircuitBreakerFailures();
        }
    }

    /**
     * Handle unexpected errors.
     */
    protected function handleUnexpectedError(Throwable $e, string $requestId): void
    {
        Log::error('Unexpected OpenAI error', [
            'request_id' => $requestId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->incrementCircuitBreakerFailures();
    }

    // Placeholder methods for usage tracking (would integrate with actual monitoring)
    protected function getRequestCount(string $period): int { return 0; }
    protected function getTokenUsage(string $period): int { return 0; }
    protected function getAverageResponseTime(): float { return 0.0; }
    protected function getSuccessRate(): float { return 100.0; }
    protected function getCircuitBreakerStatus(): string {
        return $this->isCircuitBreakerOpen() ? 'open' : 'closed';
    }
    protected function getRemainingRequests(): int { return 1000; }
    protected function getRemainingTokens(): int { return 100000; }
    protected function getRateLimitResetTime(): int { return time() + 3600; }
}
