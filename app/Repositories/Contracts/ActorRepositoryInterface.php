<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Actor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Actor Repository Interface
 *
 * Defines the contract for actor data access operations.
 * Implements repository pattern for data layer abstraction.
 */
interface ActorRepositoryInterface
{
    /**
     * Find actor by ID.
     */
    public function find(int $id): ?Actor;

    /**
     * Find actor by UUID.
     */
    public function findByUuid(string $uuid): ?Actor;

    /**
     * Find actor by email.
     */
    public function findByEmail(string $email): ?Actor;

    /**
     * Create a new actor.
     */
    public function create(array $data): Actor;

    /**
     * Update an existing actor.
     */
    public function update(Actor $actor, array $data): Actor;

    /**
     * Delete an actor (soft delete).
     */
    public function delete(Actor $actor): bool;

    /**
     * Get paginated actors with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get actors by status.
     */
    public function getByStatus(string $status, int $limit = null): Collection;

    /**
     * Get pending actors for processing.
     */
    public function getPendingActors(int $limit = 10): Collection;

    /**
     * Get failed actors that can be retried.
     */
    public function getRetryableActors(int $limit = 10): Collection;

    /**
     * Search actors by name.
     */
    public function searchByName(string $search, int $limit = 50): Collection;

    /**
     * Get actor statistics.
     */
    public function getStatistics(): array;

    /**
     * Get recent actors.
     */
    public function getRecent(int $days = 30, int $limit = 100): Collection;

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool;

    /**
     * Bulk update actors.
     */
    public function bulkUpdate(array $ids, array $data): int;

    /**
     * Get actors with submissions.
     */
    public function getWithSubmissions(array $filters = []): Collection;
}
