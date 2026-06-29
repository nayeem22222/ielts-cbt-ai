<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listening_attempt_answers')) {
            return;
        }

        Schema::create('listening_attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listening_attempt_id')->constrained('listening_attempts')->cascadeOnDelete();
            $table->foreignId('listening_test_id')->constrained('listening_tests')->restrictOnDelete();
            $table->foreignId('listening_question_id')->constrained('listening_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('question_number');
            $table->json('student_answer')->nullable();
            $table->json('normalized_answer')->nullable();
            $table->json('correct_answer_snapshot')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 5, 2)->default(0);
            $table->string('answer_status', 20)->default('unanswered');
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['listening_attempt_id', 'listening_question_id'], 'listening_attempt_answers_attempt_question_unique');
            $table->index('listening_attempt_id');
            $table->index('listening_test_id');
            $table->index('listening_question_id');
            $table->index('question_number');
            $table->index('is_correct');
            $table->index('answer_status');
            $table->index(['listening_attempt_id', 'answer_status'], 'listening_attempt_answers_attempt_status_idx');
        });
    }

    public function down(): void
    {
        // Intentionally no-op: this migration repairs a missing table only.
    }
};
