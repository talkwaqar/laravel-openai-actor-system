<?php

declare(strict_types=1);

namespace App\Services\Actor;

use App\DTOs\ActorData;
use App\DTOs\ActorSubmissionData;
use App\Exceptions\ActorProcessingException;
use App\Exceptions\OpenAIProcessingException;
use App\Models\Actor;
use App\Models\ActorSubmission;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Services\Contracts\ActorServiceInterface;
use App\Services\Contracts\OpenAIServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Actor Service
 *
 * Core business logic service for actor management.
 * Implements enterprise patterns:
 * - Dependency injection for testability
 * - Transaction management for data consistency
 * - Event-driven architecture for loose coupling
 * - Comprehensive error handling and logging
 * - Performance monitoring and optimization
 */
class ActorService implements ActorServiceInterface
{
    public function __construct(
        protected ActorRepositoryInterface $actorRepository,
        protected OpenAIServiceInterface $openAIService,
    ) {}

    /**
     * Submit actor information for processing.
     */
    public function submitActor(ActorSubmissionData $submissionData): Actor
    {
        Log::info('Actor submission started', [
            'email' => $submissionData->email,
            'description_length' => strlen($submissionData->description),
        ]);

        // Validate submission data
        if (!$submissionData->isValid()) {
            throw ActorProcessingException::validationFailed(
                $submissionData->validateData(),
                $submissionData->email
            );
        }

        // Check for duplicate email
        if ($this->actorRepository->emailExists($submissionData->email)) {
            throw ActorProcessingException::duplicateEmail($submissionData->email);
        }

        return DB::transaction(function () use ($submissionData) {
            try {
                // Create initial actor record
                $actor = $this->createInitialActor($submissionData);

                // Create submission record for audit trail
                $this->createSubmissionRecord($actor, $submissionData);

                // Process the actor description
                $processedActor = $this->processActorDescription($actor);

                // Fire events for loose coupling
                Event::dispatch('actor.submitted', $processedActor);

                Log::info('Actor submission completed successfully', [
                    'actor_id' => $processedActor->id,
                    'actor_uuid' => $processedActor->uuid,
                    'email' => $processedActor->email,
                ]);

                return $processedActor;

            } catch (Throwable $e) {
                Log::error('Actor submission failed', [
                    'email' => $submissionData->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Re-throw known exceptions
                if ($e instanceof ActorProcessingException || $e instanceof OpenAIProcessingException) {
                    throw $e;
                }

                // Wrap unexpected exceptions
                throw new ActorProcessingException(
                    'Unexpected error during actor submission',
                    ['system_error' => $e->getMessage()],
                    $submissionData->email
                );
            }
        });
    }

    /**
     * Process actor description using OpenAI.
     */
    public function processActorDescription(Actor $actor): Actor
    {
        Log::info('Processing actor description', [
            'actor_id' => $actor->id,
            'actor_uuid' => $actor->uuid,
        ]);

        try {
            // Extract information using OpenAI
            $openAIResponse = $this->openAIService->extractActorInformation(
                $actor->original_description
            );

            // Validate extracted information
            if (!$openAIResponse->hasRequiredFields()) {
                $missingFields = $openAIResponse->getMissingRequiredFields();
                throw ActorProcessingException::missingRequiredFields(
                    $missingFields,
                    $actor->email
                );
            }

            // Create actor data from OpenAI response
            $actorData = ActorData::fromOpenAIResponse(
                $actor->email,
                $actor->original_description,
                $openAIResponse
            );

            // Update actor with extracted information
            $updatedActor = $this->actorRepository->update(
                $actor,
                array_merge($actorData->toModelArray(), [
                    'status' => 'processed',
                    'processed_at' => now(),
                ])
            );

            // Update submission record
            $this->updateSubmissionRecord($actor, $openAIResponse, 'completed');

            Event::dispatch('actor.processed', [$updatedActor]);

            Log::info('Actor processing completed successfully', [
                'actor_id' => $updatedActor->id,
                'confidence_score' => $openAIResponse->getConfidenceScore(),
                'tokens_used' => $openAIResponse->tokensUsed,
            ]);

            return $updatedActor;

        } catch (OpenAIProcessingException $e) {
            // Mark actor as failed
            $this->markActorAsFailed($actor, $e->getMessage());
            $this->updateSubmissionRecord($actor, null, 'failed', $e->getMessage());

            Event::dispatch('actor.processing_failed', [$actor, $e]);

            throw $e;

        } catch (ActorProcessingException $e) {
            // Mark actor as failed
            $this->markActorAsFailed($actor, $e->getMessage());
            $this->updateSubmissionRecord($actor, null, 'failed', $e->getMessage());

            Event::dispatch('actor.processing_failed', [$actor, $e]);

            throw $e;

        } catch (Throwable $e) {
            // Handle unexpected errors
            $this->markActorAsFailed($actor, 'Unexpected processing error');
            $this->updateSubmissionRecord($actor, null, 'failed', $e->getMessage());

            Log::error('Unexpected error during actor processing', [
                'actor_id' => $actor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Event::dispatch('actor.processing_failed', [$actor, $e]);

            throw new ActorProcessingException(
                'Unexpected error during processing',
                ['system_error' => $e->getMessage()],
                $actor->email
            );
        }
    }

    /**
     * Get paginated list of actors.
     */
    public function getActors(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->actorRepository->getPaginated($filters, $perPage);
    }

    /**
     * Get actor by UUID.
     */
    public function getActorByUuid(string $uuid): Actor
    {
        $actor = $this->actorRepository->findByUuid($uuid);

        if (!$actor) {
            throw new ModelNotFoundException("Actor with UUID {$uuid} not found");
        }

        return $actor;
    }

    /**
     * Get actor by email.
     */
    public function getActorByEmail(string $email): ?Actor
    {
        return $this->actorRepository->findByEmail($email);
    }

    /**
     * Update actor information.
     */
    public function updateActor(Actor $actor, ActorData $actorData): Actor
    {
        Log::info('Updating actor', [
            'actor_id' => $actor->id,
            'actor_uuid' => $actor->uuid,
        ]);

        return DB::transaction(function () use ($actor, $actorData) {
            $updatedActor = $this->actorRepository->update(
                $actor,
                $actorData->toModelArray()
            );

            Event::dispatch('actor.updated', $updatedActor);

            return $updatedActor;
        });
    }

    /**
     * Delete actor (soft delete).
     */
    public function deleteActor(Actor $actor): bool
    {
        Log::info('Deleting actor', [
            'actor_id' => $actor->id,
            'actor_uuid' => $actor->uuid,
        ]);

        $result = $this->actorRepository->delete($actor);

        if ($result) {
            Event::dispatch('actor.deleted', $actor);
        }

        return $result;
    }

    /**
     * Get actor statistics.
     */
    public function getActorStatistics(): array
    {
        return $this->actorRepository->getStatistics();
    }

    /**
     * Retry failed actor processing.
     */
    public function retryActorProcessing(Actor $actor): Actor
    {
        if (!$actor->hasFailed()) {
            throw new ActorProcessingException(
                'Actor is not in failed state',
                ['current_status' => $actor->status],
                $actor->email
            );
        }

        Log::info('Retrying actor processing', [
            'actor_id' => $actor->id,
            'actor_uuid' => $actor->uuid,
        ]);

        // Reset actor status
        $actor = $this->actorRepository->update($actor, [
            'status' => 'pending',
            'processed_at' => null,
        ]);

        // Process again
        return $this->processActorDescription($actor);
    }

    /**
     * Bulk process pending actors.
     */
    public function bulkProcessPendingActors(int $limit = 10): Collection
    {
        $pendingActors = $this->actorRepository->getPendingActors($limit);
        $results = collect();

        Log::info('Starting bulk processing', [
            'actor_count' => $pendingActors->count(),
            'limit' => $limit,
        ]);

        foreach ($pendingActors as $actor) {
            try {
                $processedActor = $this->processActorDescription($actor);
                $results->push($processedActor);
            } catch (Throwable $e) {
                Log::warning('Bulk processing failed for actor', [
                    'actor_id' => $actor->id,
                    'error' => $e->getMessage(),
                ]);

                $results->push($actor->fresh());
            }
        }

        Log::info('Bulk processing completed', [
            'processed_count' => $results->count(),
            'success_count' => $results->where('status', 'processed')->count(),
            'failed_count' => $results->where('status', 'failed')->count(),
        ]);

        return $results;
    }

    /**
     * Create initial actor record.
     */
    protected function createInitialActor(ActorSubmissionData $submissionData): Actor
    {
        return $this->actorRepository->create([
            'email' => $submissionData->email,
            'original_description' => $submissionData->description,
            'status' => 'pending',
            'first_name' => '',
            'last_name' => '',
            'address' => '',
        ]);
    }

    /**
     * Create submission record for audit trail.
     */
    protected function createSubmissionRecord(Actor $actor, ActorSubmissionData $submissionData): ActorSubmission
    {
        return ActorSubmission::create([
            'actor_id' => $actor->id,
            'submission_email' => $submissionData->email,
            'original_description' => $submissionData->description,
            'processing_status' => 'pending',
            'ip_address' => $submissionData->ipAddress,
            'user_agent' => $submissionData->userAgent,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Update submission record with processing results.
     */
    protected function updateSubmissionRecord(
        Actor $actor,
        $openAIResponse = null,
        string $status = 'completed',
        ?string $errorMessage = null
    ): void {
        $submission = $actor->submissions()->latest()->first();

        if ($submission) {
            $submission->update([
                'processing_status' => $status,
                'openai_response_payload' => $openAIResponse?->toArray(),
                'error_message' => $errorMessage,
                'processed_at' => now(),
            ]);
        }
    }

    /**
     * Mark actor as failed.
     */
    protected function markActorAsFailed(Actor $actor, string $reason): void
    {
        $this->actorRepository->update($actor, [
            'status' => 'failed',
        ]);

        Log::warning('Actor marked as failed', [
            'actor_id' => $actor->id,
            'reason' => $reason,
        ]);
    }
}
