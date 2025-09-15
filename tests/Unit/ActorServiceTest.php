<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\ActorSubmissionData;
use App\DTOs\OpenAIResponseData;
use App\Exceptions\ActorProcessingException;
use App\Models\Actor;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Services\Actor\ActorService;
use App\Services\Contracts\OpenAIServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Actor Service Unit Tests
 *
 * Comprehensive unit testing for the ActorService class demonstrating:
 * - Dependency injection mocking
 * - Business logic validation
 * - Error handling scenarios
 * - Edge case coverage
 */
class ActorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActorRepositoryInterface $actorRepository;
    protected OpenAIServiceInterface $openAIService;
    protected ActorService $actorService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actorRepository = Mockery::mock(ActorRepositoryInterface::class);
        $this->openAIService = Mockery::mock(OpenAIServiceInterface::class);

        $this->actorService = new ActorService(
            $this->actorRepository,
            $this->openAIService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_submit_actor_successfully(): void
    {
        // Arrange
        $submissionData = new ActorSubmissionData(
            email: 'john.doe@example.com',
            description: 'My name is John Doe, I am 30 years old, 6 feet tall, and live at 123 Main St, Los Angeles, CA.'
        );

        $openAIResponse = new OpenAIResponseData(
            firstName: 'John',
            lastName: 'Doe',
            address: '123 Main St, Los Angeles, CA',
            height: '6 feet',
            weight: null,
            gender: 'male',
            age: 30
        );

        $actor = new Actor([
            'id' => 1,
            'uuid' => 'test-uuid',
            'email' => 'john.doe@example.com',
            'original_description' => $submissionData->description,
            'status' => 'pending',
        ]);

        // Mock expectations
        $this->actorRepository
            ->shouldReceive('emailExists')
            ->with($submissionData->email)
            ->once()
            ->andReturn(false);

        $this->actorRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($actor);

        $this->openAIService
            ->shouldReceive('extractActorInformation')
            ->with($submissionData->description)
            ->once()
            ->andReturn($openAIResponse);

        $this->actorRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($actor);

        // Act
        $result = $this->actorService->submitActor($submissionData);

        // Assert
        $this->assertInstanceOf(Actor::class, $result);
        $this->assertEquals('john.doe@example.com', $result->email);
    }

    /** @test */
    public function it_throws_exception_for_duplicate_email(): void
    {
        // Arrange
        $submissionData = new ActorSubmissionData(
            email: 'existing@example.com',
            description: 'Test description with name and address information.'
        );

        $this->actorRepository
            ->shouldReceive('emailExists')
            ->with($submissionData->email)
            ->once()
            ->andReturn(true);

        // Act & Assert
        $this->expectException(ActorProcessingException::class);
        $this->expectExceptionMessage("Actor with email 'existing@example.com' already exists");

        $this->actorService->submitActor($submissionData);
    }

    /** @test */
    public function it_throws_exception_for_missing_required_fields(): void
    {
        // Arrange
        $submissionData = new ActorSubmissionData(
            email: 'test@example.com',
            description: 'Incomplete description without required information.'
        );

        $openAIResponse = new OpenAIResponseData(
            firstName: '',
            lastName: 'Doe',
            address: '',
            height: null,
            weight: null,
            gender: null,
            age: null
        );

        $actor = new Actor([
            'id' => 1,
            'uuid' => 'test-uuid',
            'email' => 'test@example.com',
            'original_description' => $submissionData->description,
            'status' => 'pending',
        ]);

        $this->actorRepository
            ->shouldReceive('emailExists')
            ->andReturn(false);

        $this->actorRepository
            ->shouldReceive('create')
            ->andReturn($actor);

        $this->openAIService
            ->shouldReceive('extractActorInformation')
            ->andReturn($openAIResponse);

        $this->actorRepository
            ->shouldReceive('update')
            ->with($actor, ['status' => 'failed'])
            ->andReturn($actor);

        // Act & Assert
        $this->expectException(ActorProcessingException::class);
        $this->expectExceptionMessage('Missing required fields: first_name, address');

        $this->actorService->processActorDescription($actor);
    }

    /** @test */
    public function it_can_get_actor_by_uuid(): void
    {
        // Arrange
        $uuid = 'test-uuid-123';
        $actor = new Actor([
            'uuid' => $uuid,
            'email' => 'test@example.com',
        ]);

        $this->actorRepository
            ->shouldReceive('findByUuid')
            ->with($uuid)
            ->once()
            ->andReturn($actor);

        // Act
        $result = $this->actorService->getActorByUuid($uuid);

        // Assert
        $this->assertInstanceOf(Actor::class, $result);
        $this->assertEquals($uuid, $result->uuid);
    }

    /** @test */
    public function it_can_get_actor_statistics(): void
    {
        // Arrange
        $expectedStats = [
            'total' => 100,
            'processed' => 80,
            'pending' => 15,
            'failed' => 5,
        ];

        $this->actorRepository
            ->shouldReceive('getStatistics')
            ->once()
            ->andReturn($expectedStats);

        // Act
        $result = $this->actorService->getActorStatistics();

        // Assert
        $this->assertEquals($expectedStats, $result);
    }

    /** @test */
    public function it_can_retry_failed_actor_processing(): void
    {
        // Arrange
        $actor = new Actor([
            'id' => 1,
            'uuid' => 'test-uuid',
            'email' => 'test@example.com',
            'status' => 'failed',
            'original_description' => 'Test description',
        ]);

        $openAIResponse = new OpenAIResponseData(
            firstName: 'John',
            lastName: 'Doe',
            address: '123 Main St',
            height: null,
            weight: null,
            gender: null,
            age: null
        );

        $this->actorRepository
            ->shouldReceive('update')
            ->twice()
            ->andReturn($actor);

        $this->openAIService
            ->shouldReceive('extractActorInformation')
            ->once()
            ->andReturn($openAIResponse);

        // Act
        $result = $this->actorService->retryActorProcessing($actor);

        // Assert
        $this->assertInstanceOf(Actor::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_retrying_non_failed_actor(): void
    {
        // Arrange
        $actor = new Actor([
            'status' => 'processed',
            'email' => 'test@example.com',
        ]);

        // Act & Assert
        $this->expectException(ActorProcessingException::class);
        $this->expectExceptionMessage('Actor is not in failed state');

        $this->actorService->retryActorProcessing($actor);
    }

    /** @test */
    public function it_can_bulk_process_pending_actors(): void
    {
        // Arrange
        $pendingActors = collect([
            new Actor(['id' => 1, 'status' => 'pending', 'original_description' => 'Test 1']),
            new Actor(['id' => 2, 'status' => 'pending', 'original_description' => 'Test 2']),
        ]);

        $openAIResponse = new OpenAIResponseData(
            firstName: 'John',
            lastName: 'Doe',
            address: '123 Main St',
            height: null,
            weight: null,
            gender: null,
            age: null
        );

        $this->actorRepository
            ->shouldReceive('getPendingActors')
            ->with(10)
            ->once()
            ->andReturn($pendingActors);

        $this->openAIService
            ->shouldReceive('extractActorInformation')
            ->twice()
            ->andReturn($openAIResponse);

        $this->actorRepository
            ->shouldReceive('update')
            ->twice()
            ->andReturn($pendingActors->first());

        // Act
        $result = $this->actorService->bulkProcessPendingActors(10);

        // Assert
        $this->assertCount(2, $result);
    }
}
