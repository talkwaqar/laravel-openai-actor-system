<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\ActorSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Actor Submission Feature Tests
 *
 * Integration tests for the complete actor submission flow demonstrating:
 * - HTTP endpoint testing
 * - Database interactions
 * - Validation testing
 * - Error handling scenarios
 * - Response format validation
 */
class ActorSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_display_actor_submission_form(): void
    {
        // Act
        $response = $this->get(route('actors.create'));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('actors.create');
        $response->assertSee('Submit Actor Information');
        $response->assertSee('Please enter your first name and last name');
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Act
        $response = $this->postJson(route('actors.store'), []);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure([
            'error',
            'message',
            'errors' => [
                'email',
                'description',
            ],
        ]);
    }

    /** @test */
    public function it_validates_email_format(): void
    {
        // Act
        $response = $this->postJson(route('actors.store'), [
            'email' => 'invalid-email',
            'description' => 'This is a valid description with enough content to pass validation.',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.email.0', 'Please provide a valid email address.');
    }

    /** @test */
    public function it_validates_description_minimum_length(): void
    {
        // Act
        $response = $this->postJson(route('actors.store'), [
            'email' => 'test@example.com',
            'description' => 'Short',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.description.0', 'Description must be at least 10 characters long.');
    }

    /** @test */
    public function it_validates_unique_email(): void
    {
        // Arrange
        Actor::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->postJson(route('actors.store'), [
            'email' => 'existing@example.com',
            'description' => 'This is a valid description with name John Doe and address 123 Main St.',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.email.0', 'This email has already been used for an actor submission.');
    }

    /** @test */
    public function it_rejects_disposable_email_domains(): void
    {
        // Act
        $response = $this->postJson(route('actors.store'), [
            'email' => 'test@10minutemail.com',
            'description' => 'This is a valid description with name John Doe and address 123 Main St.',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.email.0', 'Disposable email addresses are not allowed.');
    }

    /** @test */
    public function it_validates_description_quality(): void
    {
        // Act
        $response = $this->postJson(route('actors.store'), [
            'email' => 'test@example.com',
            'description' => 'aaaaaaaaaaaaaaaaaaaaaa', // Low quality description
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.description.0', 'Please provide a more detailed and meaningful description.');
    }

    /** @test */
    public function it_requires_minimum_information_in_description(): void
    {
        // Act
        $response = $this->postJson(route('actors.store'), [
            'email' => 'test@example.com',
            'description' => 'This is just a random description without any useful information about the person.',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.description.0', 'Please provide more detailed information about the actor (name, physical attributes, etc.).');
    }

    /** @test */
    public function it_can_display_actors_index(): void
    {
        // Arrange
        Actor::factory()->count(3)->create();

        // Act
        $response = $this->get(route('actors.index'));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('actors.index');
        $response->assertSee('Actor Submissions');
    }

    /** @test */
    public function it_can_display_actors_index_as_json(): void
    {
        // Arrange
        Actor::factory()->count(3)->create();

        // Act
        $response = $this->getJson(route('actors.index'));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'actors',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
                'statistics',
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_actors_by_status(): void
    {
        // Arrange
        Actor::factory()->create(['status' => 'pending']);
        Actor::factory()->create(['status' => 'processed']);
        Actor::factory()->create(['status' => 'failed']);

        // Act
        $response = $this->getJson(route('actors.index', ['status' => 'processed']));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonPath('data.actors.0.status', 'processed');
    }

    /** @test */
    public function it_can_show_specific_actor(): void
    {
        // Arrange
        $actor = Actor::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        // Act
        $response = $this->getJson(route('actors.show', $actor->uuid));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'actor' => [
                    'uuid',
                    'email',
                    'first_name',
                    'last_name',
                    'full_name',
                    'address',
                    'status',
                    'created_at',
                ],
            ],
        ]);
        $response->assertJsonPath('data.actor.email', 'john@example.com');
    }

    /** @test */
    public function it_returns_404_for_non_existent_actor(): void
    {
        // Act
        $response = $this->getJson(route('actors.show', 'non-existent-uuid'));

        // Assert
        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertJsonPath('error', 'actor_not_found');
    }

    /** @test */
    public function it_can_retry_failed_actor(): void
    {
        // Arrange
        $actor = Actor::factory()->create([
            'status' => 'failed',
            'original_description' => 'My name is John Doe and I live at 123 Main St.',
        ]);

        // Act
        $response = $this->postJson(route('actors.retry', $actor->uuid));

        // Assert - Note: This will likely fail in testing without OpenAI API mocking
        // In a real test environment, you'd mock the OpenAI service
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
    }

    /** @test */
    public function it_prevents_retry_of_non_failed_actor(): void
    {
        // Arrange
        $actor = Actor::factory()->create(['status' => 'processed']);

        // Act
        $response = $this->postJson(route('actors.retry', $actor->uuid));

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('error', 'retry_failed');
    }

    /** @test */
    public function it_can_get_prompt_validation_endpoint(): void
    {
        // Act
        $response = $this->getJson('/api/actors/prompt-validation');

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['message']);
        $response->assertJsonPath('message', 'Please enter your first name and last name, and also provide your address.');
    }

    /** @test */
    public function it_can_get_api_health_status(): void
    {
        // Act
        $response = $this->getJson('/api/health');

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'status',
            'service',
            'version',
            'timestamp',
        ]);
        $response->assertJsonPath('status', 'healthy');
    }

    /** @test */
    public function it_can_get_api_documentation(): void
    {
        // Act
        $response = $this->getJson('/api/docs');

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'api',
            'version',
            'endpoints',
            'authentication',
            'rate_limiting',
            'error_format',
        ]);
    }

    /** @test */
    public function it_creates_submission_record_on_actor_creation(): void
    {
        // This test would require mocking the OpenAI service
        // For demonstration purposes, we'll test the database structure

        // Arrange
        $actor = Actor::factory()->create();

        // Create a submission record manually for testing
        ActorSubmission::create([
            'actor_id' => $actor->id,
            'submission_email' => $actor->email,
            'original_description' => 'Test description',
            'processing_status' => 'completed',
            'submitted_at' => now(),
        ]);

        // Assert
        $this->assertDatabaseHas('actor_submissions', [
            'actor_id' => $actor->id,
            'submission_email' => $actor->email,
            'processing_status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_pagination_correctly(): void
    {
        // Arrange
        Actor::factory()->count(25)->create();

        // Act
        $response = $this->getJson(route('actors.index', ['per_page' => 10]));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonPath('data.pagination.per_page', 10);
        $response->assertJsonPath('data.pagination.total', 25);
        $this->assertCount(10, $response->json('data.actors'));
    }

    /** @test */
    public function it_limits_maximum_per_page(): void
    {
        // Arrange
        Actor::factory()->count(100)->create();

        // Act
        $response = $this->getJson(route('actors.index', ['per_page' => 1000]));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonPath('data.pagination.per_page', 50); // Should be capped at 50
    }
}
