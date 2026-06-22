<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_prompt_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->json('request_payload')->nullable();
            $table->unsignedInteger('tokens_prompt')->nullable();
            $table->unsignedInteger('tokens_completion')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('student_answer_id');
            $table->index('test_attempt_id');
            $table->index('ai_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
