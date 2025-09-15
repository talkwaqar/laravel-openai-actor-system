<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ActorRepository;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Services\Actor\ActorService;
use App\Services\Contracts\ActorServiceInterface;
use App\Services\Contracts\OpenAIServiceInterface;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\ServiceProvider;

/**
 * Actor Service Provider
 *
 * Registers service layer dependencies and implements dependency injection
 * for enterprise-level architecture with proper interface bindings.
 */
class ActorServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     */
    public array $bindings = [
        ActorRepositoryInterface::class => ActorRepository::class,
        ActorServiceInterface::class => ActorService::class,
        OpenAIServiceInterface::class => OpenAIService::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ActorRepositoryInterface::class, ActorRepository::class);

        // Service bindings
        $this->app->bind(ActorServiceInterface::class, ActorService::class);
        $this->app->bind(OpenAIServiceInterface::class, OpenAIService::class);

        // Singleton bindings for performance-critical services
        $this->app->singleton(OpenAIServiceInterface::class, OpenAIService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register event listeners
        $this->registerEventListeners();

        // Register custom validation rules
        $this->registerValidationRules();
    }

    /**
     * Register event listeners for actor operations.
     */
    protected function registerEventListeners(): void
    {
        // Actor submitted event
        $this->app['events']->listen('actor.submitted', function ($actor) {
            \Illuminate\Support\Facades\Log::info('Actor submitted event fired', [
                'actor_id' => $actor->id,
                'actor_uuid' => $actor->uuid,
            ]);
        });

        // Actor processed event
        $this->app['events']->listen('actor.processed', function ($actor) {
            \Illuminate\Support\Facades\Log::info('Actor processed event fired', [
                'actor_id' => $actor->id,
                'actor_uuid' => $actor->uuid,
            ]);
        });

        // Actor processing failed event
        $this->app['events']->listen('actor.processing_failed', function ($actor, $exception) {
            \Illuminate\Support\Facades\Log::error('Actor processing failed event fired', [
                'actor_id' => $actor->id,
                'actor_uuid' => $actor->uuid,
                'error' => $exception->getMessage(),
            ]);
        });

        // Actor updated event
        $this->app['events']->listen('actor.updated', function ($actor) {
            \Illuminate\Support\Facades\Log::info('Actor updated event fired', [
                'actor_id' => $actor->id,
                'actor_uuid' => $actor->uuid,
            ]);
        });

        // Actor deleted event
        $this->app['events']->listen('actor.deleted', function ($actor) {
            \Illuminate\Support\Facades\Log::info('Actor deleted event fired', [
                'actor_id' => $actor->id,
                'actor_uuid' => $actor->uuid,
            ]);
        });
    }

    /**
     * Register custom validation rules.
     */
    protected function registerValidationRules(): void
    {
        // Custom validation rule for unique actor email
        \Illuminate\Support\Facades\Validator::extend('unique_actor_email', function ($attribute, $value, $parameters, $validator) {
            $repository = $this->app->make(ActorRepositoryInterface::class);
            return !$repository->emailExists($value);
        });

        // Custom validation rule for actor description quality
        \Illuminate\Support\Facades\Validator::extend('quality_description', function ($attribute, $value, $parameters, $validator) {
            // Check minimum word count
            $wordCount = str_word_count($value);
            if ($wordCount < 5) {
                return false;
            }

            // Check for meaningful content (not just repeated characters)
            $uniqueChars = count(array_unique(str_split(strtolower($value))));
            if ($uniqueChars < 5) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ActorRepositoryInterface::class,
            ActorServiceInterface::class,
            OpenAIServiceInterface::class,
        ];
    }
}
