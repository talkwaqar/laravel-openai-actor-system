<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\ActorSubmissionData;
use App\DTOs\ActorData;
use App\Models\Actor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Actor Service Interface
 *
 * Defines the contract for actor-related business operations.
 * Implements dependency inversion principle for testability and flexibility.
 */
interface ActorServiceInterface
{
    /**
     * Submit actor information for processing.
     *
     * @param ActorSubmissionData $submissionData
     * @return Actor
     * @throws \App\Exceptions\ActorProcessingException
     */
    public function submitActor(ActorSubmissionData $submissionData): Actor;

    /**
     * Process actor description using OpenAI.
     *
     * @param Actor $actor
     * @return Actor
     * @throws \App\Exceptions\OpenAIProcessingException
     */
    public function processActorDescription(Actor $actor): Actor;

    /**
     * Get paginated list of actors.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActors(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get actor by UUID.
     *
     * @param string $uuid
     * @return Actor
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getActorByUuid(string $uuid): Actor;

    /**
     * Get actor by email.
     *
     * @param string $email
     * @return Actor|null
     */
    public function getActorByEmail(string $email): ?Actor;

    /**
     * Update actor information.
     *
     * @param Actor $actor
     * @param ActorData $actorData
     * @return Actor
     */
    public function updateActor(Actor $actor, ActorData $actorData): Actor;

    /**
     * Delete actor (soft delete).
     *
     * @param Actor $actor
     * @return bool
     */
    public function deleteActor(Actor $actor): bool;

    /**
     * Get actor statistics.
     *
     * @return array
     */
    public function getActorStatistics(): array;

    /**
     * Retry failed actor processing.
     *
     * @param Actor $actor
     * @return Actor
     * @throws \App\Exceptions\ActorProcessingException
     */
    public function retryActorProcessing(Actor $actor): Actor;

    /**
     * Bulk process pending actors.
     *
     * @param int $limit
     * @return Collection
     */
    public function bulkProcessPendingActors(int $limit = 10): Collection;
}
