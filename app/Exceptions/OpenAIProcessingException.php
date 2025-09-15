<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * OpenAI Processing Exception
 *
 * Thrown when OpenAI API calls fail or return invalid responses.
 * Implements circuit breaker pattern support and retry logic.
 */
class OpenAIProcessingException extends Exception
{
    protected ?array $apiResponse = null;
    protected ?string $requestId = null;
    protected bool $retryable = false;

    public function __construct(
        string $message = 'OpenAI processing failed',
        ?array $apiResponse = null,
        ?string $requestId = null,
        bool $retryable = false,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->apiResponse = $apiResponse;
        $this->requestId = $requestId;
        $this->retryable = $retryable;
    }

    /**
     * Create exception for API rate limiting.
     */
    public static function rateLimitExceeded(?string $requestId = null): self
    {
        return new self(
            message: 'OpenAI API rate limit exceeded',
            requestId: $requestId,
            retryable: true,
            code: 429
        );
    }

    /**
     * Create exception for API authentication failure.
     */
    public static function authenticationFailed(?string $requestId = null): self
    {
        return new self(
            message: 'OpenAI API authentication failed',
            requestId: $requestId,
            retryable: false,
            code: 401
        );
    }

    /**
     * Create exception for invalid API response.
     */
    public static function invalidResponse(array $response, ?string $requestId = null): self
    {
        return new self(
            message: 'OpenAI API returned invalid response format',
            apiResponse: $response,
            requestId: $requestId,
            retryable: false,
            code: 502
        );
    }

    /**
     * Create exception for API timeout.
     */
    public static function timeout(?string $requestId = null): self
    {
        return new self(
            message: 'OpenAI API request timed out',
            requestId: $requestId,
            retryable: true,
            code: 408
        );
    }

    /**
     * Create exception for API server error.
     */
    public static function serverError(int $statusCode, ?array $response = null, ?string $requestId = null): self
    {
        return new self(
            message: "OpenAI API server error (HTTP {$statusCode})",
            apiResponse: $response,
            requestId: $requestId,
            retryable: $statusCode >= 500,
            code: $statusCode
        );
    }

    /**
     * Create exception for insufficient tokens/credits.
     */
    public static function insufficientCredits(?string $requestId = null): self
    {
        return new self(
            message: 'Insufficient OpenAI API credits',
            requestId: $requestId,
            retryable: false,
            code: 402
        );
    }

    /**
     * Create exception for content policy violation.
     */
    public static function contentPolicyViolation(?array $response = null, ?string $requestId = null): self
    {
        return new self(
            message: 'Content violates OpenAI usage policies',
            apiResponse: $response,
            requestId: $requestId,
            retryable: false,
            code: 400
        );
    }

    /**
     * Create exception for network connectivity issues.
     */
    public static function networkError(string $details, ?string $requestId = null): self
    {
        return new self(
            message: "Network error: {$details}",
            requestId: $requestId,
            retryable: true,
            code: 0
        );
    }

    /**
     * Get API response if available.
     */
    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }

    /**
     * Get request ID if available.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Check if the operation can be retried.
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->code >= 500;
    }

    /**
     * Convert to array for logging and API responses.
     */
    public function toArray(): array
    {
        return [
            'error' => 'openai_processing_failed',
            'message' => $this->getMessage(),
            'request_id' => $this->requestId,
            'retryable' => $this->retryable,
            'code' => $this->getCode(),
            'api_response' => $this->apiResponse,
        ];
    }
}
