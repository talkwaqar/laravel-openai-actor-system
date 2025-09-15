<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ActorSubmissionRequest;
use App\Services\Contracts\ActorServiceInterface;
use App\Exceptions\ActorProcessingException;
use App\Exceptions\OpenAIProcessingException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Actor Controller
 *
 * Thin controller implementing enterprise patterns:
 * - Dependency injection for services
 * - Proper HTTP status codes and responses
 * - Comprehensive error handling
 * - Request/response transformation
 * - Performance monitoring and logging
 */
class ActorController
{
    public function __construct(
        protected ActorServiceInterface $actorService
    ) {}

    /**
     * Store a new actor submission.
     */
    public function store(ActorSubmissionRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            Log::info('Actor submission request received', [
                'email' => $request->input('email'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Convert request to DTO
            $submissionData = $request->toDTO();

            // Process through service layer
            $actor = $this->actorService->submitActor($submissionData);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Actor submission completed', [
                'actor_id' => $actor->id,
                'actor_uuid' => $actor->uuid,
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Actor information submitted successfully',
                'data' => [
                    'actor' => [
                        'uuid' => $actor->uuid,
                        'email' => $actor->email,
                        'status' => $actor->status,
                        'created_at' => $actor->created_at->toISOString(),
                    ],
                    'redirect_url' => route('actors.index'),
                ],
                'meta' => [
                    'processing_time_ms' => $processingTime,
                ],
            ], Response::HTTP_CREATED);

        } catch (ActorProcessingException $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::warning('Actor submission failed - business logic error', [
                'error' => $e->toArray(),
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'actor_processing_failed',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'meta' => [
                    'processing_time_ms' => $processingTime,
                ],
            ], $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (OpenAIProcessingException $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Actor submission failed - OpenAI error', [
                'error' => $e->toArray(),
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'openai_processing_failed',
                'message' => 'Unable to process actor information at this time. Please try again later.',
                'meta' => [
                    'processing_time_ms' => $processingTime,
                    'retryable' => $e->isRetryable(),
                ],
            ], Response::HTTP_SERVICE_UNAVAILABLE);

        } catch (Throwable $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Actor submission failed - unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'internal_server_error',
                'message' => 'An unexpected error occurred. Please try again later.',
                'meta' => [
                    'processing_time_ms' => $processingTime,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display a listing of actors.
     */
    public function index(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $perPage = min((int) $request->get('per_page', 15), 50); // Max 50 per page

            $actors = $this->actorService->getActors($filters, $perPage);
            $statistics = $this->actorService->getActorStatistics();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'actors' => collect($actors->items())->map(function ($actor) {
                            return [
                                'id' => $actor->id,
                                'uuid' => $actor->uuid,
                                'email' => $actor->email,
                                'first_name' => $actor->first_name,
                                'last_name' => $actor->last_name,
                                'full_name' => $actor->full_name, // Include the accessor
                                'address' => $actor->address,
                                'height' => $actor->height,
                                'weight' => $actor->weight,
                                'gender' => $actor->gender,
                                'display_gender' => $actor->display_gender, // Include the accessor
                                'age' => $actor->age,
                                'original_description' => $actor->original_description,
                                'status' => $actor->status,
                                'processed_at' => $actor->processed_at?->toISOString(),
                                'created_at' => $actor->created_at->toISOString(),
                                'updated_at' => $actor->updated_at->toISOString(),
                                'deleted_at' => $actor->deleted_at?->toISOString(),
                            ];
                        }),
                        'pagination' => [
                            'current_page' => $actors->currentPage(),
                            'last_page' => $actors->lastPage(),
                            'per_page' => $actors->perPage(),
                            'total' => $actors->total(),
                        ],
                        'statistics' => $statistics,
                    ],
                ]);
            }

            return view('actors.index', compact('actors', 'statistics', 'filters'));

        } catch (Throwable $e) {
            Log::error('Failed to retrieve actors', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'retrieval_failed',
                    'message' => 'Unable to retrieve actors at this time.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->with('error', 'Unable to retrieve actors at this time.');
        }
    }

    /**
     * Display the specified actor.
     */
    public function show(Request $request, string $uuid)
    {
        try {
            $actor = $this->actorService->getActorByUuid($uuid);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'actor' => [
                            'uuid' => $actor->uuid,
                            'email' => $actor->email,
                            'first_name' => $actor->first_name,
                            'last_name' => $actor->last_name,
                            'full_name' => $actor->full_name,
                            'address' => $actor->address,
                            'height' => $actor->height,
                            'weight' => $actor->weight,
                            'gender' => $actor->gender,
                            'display_gender' => $actor->display_gender,
                            'age' => $actor->age,
                            'status' => $actor->status,
                            'original_description' => $actor->original_description,
                            'created_at' => $actor->created_at->toISOString(),
                            'processed_at' => $actor->processed_at?->toISOString(),
                        ],
                    ],
                ]);
            }

            // Return Blade view for web requests
            return view('actors.show', ['uuid' => $uuid]);

        } catch (Throwable $e) {
            Log::error('Failed to retrieve actor', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'actor_not_found',
                    'message' => 'Actor not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return redirect()->route('actors.index')->with('error', 'Actor not found.');
        }
    }

    /**
     * Retry processing for a failed actor.
     */
    public function retry(string $uuid): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $actor = $this->actorService->getActorByUuid($uuid);
            $retryActor = $this->actorService->retryActorProcessing($actor);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Actor retry completed', [
                'actor_id' => $retryActor->id,
                'actor_uuid' => $retryActor->uuid,
                'new_status' => $retryActor->status,
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Actor processing retried successfully',
                'data' => [
                    'actor' => [
                        'uuid' => $retryActor->uuid,
                        'status' => $retryActor->status,
                        'processed_at' => $retryActor->processed_at?->toISOString(),
                    ],
                ],
                'meta' => [
                    'processing_time_ms' => $processingTime,
                ],
            ]);

        } catch (ActorProcessingException $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => false,
                'error' => 'retry_failed',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'meta' => [
                    'processing_time_ms' => $processingTime,
                ],
            ], $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (Throwable $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Actor retry failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'retry_failed',
                'message' => 'Unable to retry actor processing at this time.',
                'meta' => [
                    'processing_time_ms' => $processingTime,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get prompt validation message.
     *
     * Required endpoint: GET /api/actors/prompt-validation
     * Returns JSON: { "message": "text_prompt" }
     */
    public function getPromptValidation(): JsonResponse
    {
        $promptMessage = "Please enter your first name and last name, and also provide your address.";

        return response()->json([
            'message' => $promptMessage,
        ], Response::HTTP_OK);
    }

    /**
     * Get API health status.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'actor-management-api',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_OK);
    }

    /**
     * Get API documentation.
     */
    public function documentation(): JsonResponse
    {
        return response()->json([
            'api' => 'Actor Management System API',
            'version' => '1.0.0',
            'endpoints' => [
                [
                    'method' => 'GET',
                    'path' => '/api/actors/prompt-validation',
                    'description' => 'Get prompt validation message',
                    'response' => [
                        'message' => 'string',
                    ],
                ],
                [
                    'method' => 'POST',
                    'path' => '/actors',
                    'description' => 'Submit actor information for processing',
                    'parameters' => [
                        'email' => 'required|email|unique',
                        'description' => 'required|string|min:10|max:2000',
                    ],
                    'response' => [
                        'success' => 'boolean',
                        'message' => 'string',
                        'data' => 'object',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/actors',
                    'description' => 'Get paginated list of actors',
                    'parameters' => [
                        'page' => 'optional|integer',
                        'per_page' => 'optional|integer|max:50',
                        'status' => 'optional|string|in:pending,processed,failed',
                        'gender' => 'optional|string',
                        'search' => 'optional|string',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/actors/{uuid}',
                    'description' => 'Get specific actor by UUID',
                ],
                [
                    'method' => 'POST',
                    'path' => '/actors/{uuid}/retry',
                    'description' => 'Retry processing for failed actor',
                ],
            ],
            'authentication' => 'None required for public endpoints',
            'rate_limiting' => '100 requests per minute per IP',
            'error_format' => [
                'success' => false,
                'error' => 'error_code',
                'message' => 'Human readable error message',
                'errors' => 'Validation errors object (if applicable)',
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Build filters from request parameters.
     */
    protected function buildFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }

        if ($request->filled('gender')) {
            $filters['gender'] = $request->input('gender');
        }

        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }

        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }

        return $filters;
    }
}
