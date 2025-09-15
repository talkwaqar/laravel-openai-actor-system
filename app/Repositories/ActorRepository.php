<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Actor;
use App\Repositories\Contracts\ActorRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Actor Repository
 *
 * Concrete implementation of actor data access operations.
 * Implements caching, query optimization, and performance monitoring.
 */
class ActorRepository implements ActorRepositoryInterface
{
    protected Actor $model;
    protected int $cacheTime = 3600; // 1 hour

    public function __construct(Actor $model)
    {
        $this->model = $model;
    }

    /**
     * Find actor by ID.
     */
    public function find(int $id): ?Actor
    {
        return Cache::remember(
            "actor.id.{$id}",
            $this->cacheTime,
            fn() => $this->model->find($id)
        );
    }

    /**
     * Find actor by UUID.
     */
    public function findByUuid(string $uuid): ?Actor
    {
        return Cache::remember(
            "actor.uuid.{$uuid}",
            $this->cacheTime,
            fn() => $this->model->where('uuid', $uuid)->first()
        );
    }

    /**
     * Find actor by email.
     */
    public function findByEmail(string $email): ?Actor
    {
        return Cache::remember(
            "actor.email.{$email}",
            $this->cacheTime,
            fn() => $this->model->where('email', $email)->first()
        );
    }

    /**
     * Create a new actor.
     */
    public function create(array $data): Actor
    {
        $actor = $this->model->create($data);

        // Clear related caches
        $this->clearCacheForEmail($actor->email);
        Cache::forget('actor.statistics');

        return $actor;
    }

    /**
     * Update an existing actor.
     */
    public function update(Actor $actor, array $data): Actor
    {
        $oldEmail = $actor->email;

        $actor->update($data);

        // Clear related caches
        $this->clearCacheForActor($actor);
        if (isset($data['email']) && $data['email'] !== $oldEmail) {
            $this->clearCacheForEmail($oldEmail);
        }
        Cache::forget('actor.statistics');

        return $actor->fresh();
    }

    /**
     * Delete an actor (soft delete).
     */
    public function delete(Actor $actor): bool
    {
        $result = $actor->delete();

        if ($result) {
            $this->clearCacheForActor($actor);
            Cache::forget('actor.statistics');
        }

        return $result;
    }

    /**
     * Get paginated actors with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        // Default ordering
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get actors by status.
     */
    public function getByStatus(string $status, int $limit = null): Collection
    {
        $cacheKey = "actors.status.{$status}.limit.{$limit}";

        return Cache::remember(
            $cacheKey,
            300, // 5 minutes for status queries
            function () use ($status, $limit) {
                $query = $this->model->byStatus($status);

                if ($limit) {
                    $query->limit($limit);
                }

                return $query->get();
            }
        );
    }

    /**
     * Get pending actors for processing.
     */
    public function getPendingActors(int $limit = 10): Collection
    {
        return $this->model->pending()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed actors that can be retried.
     */
    public function getRetryableActors(int $limit = 10): Collection
    {
        return $this->model->failed()
            ->whereNull('processed_at')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search actors by name.
     */
    public function searchByName(string $search, int $limit = 50): Collection
    {
        return $this->model->searchByName($search)
            ->limit($limit)
            ->get();
    }

    /**
     * Get actor statistics.
     */
    public function getStatistics(): array
    {
        return Cache::remember(
            'actor.statistics',
            $this->cacheTime,
            function () {
                return [
                    'total' => $this->model->count(),
                    'processed' => $this->model->processed()->count(),
                    'pending' => $this->model->pending()->count(),
                    'failed' => $this->model->failed()->count(),
                    'recent' => $this->model->recent()->count(),
                    'by_gender' => $this->getGenderStatistics(),
                    'processing_rate' => $this->getProcessingRate(),
                ];
            }
        );
    }

    /**
     * Get recent actors.
     */
    public function getRecent(int $days = 30, int $limit = 100): Collection
    {
        $cacheKey = "actors.recent.{$days}.{$limit}";

        return Cache::remember(
            $cacheKey,
            600, // 10 minutes
            fn() => $this->model->recent()
                ->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool
    {
        return Cache::remember(
            "actor.email_exists.{$email}",
            $this->cacheTime,
            fn() => $this->model->where('email', $email)->exists()
        );
    }

    /**
     * Bulk update actors.
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        $affected = $this->model->whereIn('id', $ids)->update($data);

        // Clear relevant caches
        Cache::forget('actor.statistics');
        foreach ($ids as $id) {
            Cache::forget("actor.id.{$id}");
        }

        return $affected;
    }

    /**
     * Get actors with submissions.
     */
    public function getWithSubmissions(array $filters = []): Collection
    {
        $query = $this->model->with('submissions');

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['gender'])) {
            $query->byGender($filters['gender']);
        }

        if (isset($filters['search'])) {
            $query->searchByName($filters['search']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['processed_from'])) {
            $query->where('processed_at', '>=', $filters['processed_from']);
        }

        if (isset($filters['processed_to'])) {
            $query->where('processed_at', '<=', $filters['processed_to']);
        }
    }

    /**
     * Get gender statistics.
     */
    protected function getGenderStatistics(): array
    {
        return $this->model->select('gender', DB::raw('count(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();
    }

    /**
     * Get processing rate statistics.
     */
    protected function getProcessingRate(): array
    {
        $last24h = $this->model->where('created_at', '>=', now()->subDay())->count();
        $processed24h = $this->model->processed()
            ->where('processed_at', '>=', now()->subDay())
            ->count();

        return [
            'submissions_24h' => $last24h,
            'processed_24h' => $processed24h,
            'success_rate' => $last24h > 0 ? round(($processed24h / $last24h) * 100, 2) : 0,
        ];
    }

    /**
     * Clear cache for specific actor.
     */
    protected function clearCacheForActor(Actor $actor): void
    {
        Cache::forget("actor.id.{$actor->id}");
        Cache::forget("actor.uuid.{$actor->uuid}");
        $this->clearCacheForEmail($actor->email);
    }

    /**
     * Clear cache for specific email.
     */
    protected function clearCacheForEmail(string $email): void
    {
        Cache::forget("actor.email.{$email}");
        Cache::forget("actor.email_exists.{$email}");
    }
}
