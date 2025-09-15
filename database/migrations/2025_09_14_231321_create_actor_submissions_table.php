<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('actor_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('actor_id')->constrained()->onDelete('cascade');
            $table->string('submission_email');
            $table->text('original_description');
            $table->json('openai_request_payload')->nullable();
            $table->json('openai_response_payload')->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('submitted_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance and analytics
            $table->index(['actor_id', 'processing_status']);
            $table->index(['submission_email', 'submitted_at']);
            $table->index(['processing_status', 'retry_count']);
            $table->index('submitted_at');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actor_submissions');
    }
};
