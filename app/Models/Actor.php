<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Actor Model
 *
 * Represents an actor entity with extracted information from OpenAI processing.
 * Implements enterprise patterns including UUID generation, soft deletes,
 * query scopes, and proper relationships.
 *
 * @property int $id
 * @property string $uuid
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $address
 * @property string|null $height
 * @property string|null $weight
 * @property string|null $gender
 * @property int|null $age
 * @property string $original_description
 * @property array|null $openai_response
 * @property string $status
 * @property \Carbon\Carbon|null $processed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Actor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'email',
        'first_name',
        'last_name',
        'address',
        'height',
        'weight',
        'gender',
        'age',
        'original_description',
        'openai_response',
        'status',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'openai_response' => 'array',
        'processed_at' => 'datetime',
        'age' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'openai_response',
    ];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $actor): void {
            if (empty($actor->uuid)) {
                $actor->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the actor's full name.
     */
    public function getFullNameAttribute(): string
    {
        $lastName = ($this->last_name === 'null' || $this->last_name === null) ? '' : $this->last_name;
        return trim("{$this->first_name} {$lastName}");
    }

    /**
     * Get the actor's display gender.
     */
    public function getDisplayGenderAttribute(): string
    {
        return match ($this->gender) {
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
            'prefer_not_to_say' => 'Prefer not to say',
            default => 'Not specified',
        };
    }

    /**
     * Check if the actor has been processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed' && $this->processed_at !== null;
    }

    /**
     * Check if the actor processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the actor as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark the actor as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }

    /**
     * Relationship: Actor has many submissions.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ActorSubmission::class);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter processed actors.
     */
    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('status', 'processed')
                    ->whereNotNull('processed_at');
    }

    /**
     * Scope: Filter pending actors.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter failed actors.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Search by name.
     */
    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search): void {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by gender.
     */
    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope: Recent actors (within last 30 days).
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }
}
