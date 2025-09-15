<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * ActorSubmission Model
 *
 * Tracks submission history and processing metadata for actor data.
 * Implements audit trail, retry mechanisms, and detailed logging
 * for enterprise-level tracking and debugging.
 *
 * @property int $id
 * @property string $uuid
 * @property int $actor_id
 * @property string $submission_email
 * @property string $original_description
 * @property array|null $openai_request_payload
 * @property array|null $openai_response_payload
 * @property string $processing_status
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon $submitted_at
 * @property \Carbon\Carbon|null $processed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ActorSubmission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'actor_id',
        'submission_email',
        'original_description',
        'openai_request_payload',
        'openai_response_payload',
        'processing_status',
        'error_message',
        'retry_count',
        'submitted_at',
        'processed_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'openai_request_payload' => 'array',
        'openai_response_payload' => 'array',
        'retry_count' => 'integer',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'openai_request_payload',
        'openai_response_payload',
        'ip_address',
        'user_agent',
    ];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $submission): void {
            if (empty($submission->uuid)) {
                $submission->uuid = (string) Str::uuid();
            }

            if (empty($submission->submitted_at)) {
                $submission->submitted_at = now();
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
     * Check if the submission is pending.
     */
    public function isPending(): bool
    {
        return $this->processing_status === 'pending';
    }

    /**
     * Check if the submission is processing.
     */
    public function isProcessing(): bool
    {
        return $this->processing_status === 'processing';
    }

    /**
     * Check if the submission is completed.
     */
    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    /**
     * Check if the submission has failed.
     */
    public function hasFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    /**
     * Check if the submission can be retried.
     */
    public function canRetry(): bool
    {
        return $this->hasFailed() && $this->retry_count < 3;
    }

    /**
     * Mark the submission as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'processing_status' => 'processing',
        ]);
    }

    /**
     * Mark the submission as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'processing_status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark the submission as failed with error message.
     */
    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'processing_status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Get processing duration in seconds.
     */
    public function getProcessingDurationAttribute(): ?int
    {
        if ($this->processed_at && $this->submitted_at) {
            return $this->processed_at->diffInSeconds($this->submitted_at);
        }

        return null;
    }

    /**
     * Relationship: Submission belongs to an actor.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    /**
     * Scope: Filter by processing status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Scope: Filter pending submissions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('processing_status', 'pending');
    }

    /**
     * Scope: Filter processing submissions.
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('processing_status', 'processing');
    }

    /**
     * Scope: Filter completed submissions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('processing_status', 'completed');
    }

    /**
     * Scope: Filter failed submissions.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('processing_status', 'failed');
    }

    /**
     * Scope: Filter submissions that can be retried.
     */
    public function scopeRetryable(Builder $query): Builder
    {
        return $query->where('processing_status', 'failed')
                    ->where('retry_count', '<', 3);
    }

    /**
     * Scope: Filter by submission email.
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('submission_email', $email);
    }

    /**
     * Scope: Recent submissions (within last 24 hours).
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('submitted_at', '>=', now()->subDay());
    }

    /**
     * Scope: Submissions with errors.
     */
    public function scopeWithErrors(Builder $query): Builder
    {
        return $query->whereNotNull('error_message');
    }
}
