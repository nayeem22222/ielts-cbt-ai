<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listening_review_items')) {
            return;
        }

        Schema::create('listening_review_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listening_result_id')->constrained('listening_results')->cascadeOnDelete();
            $table->foreignId('listening_attempt_id')->constrained('listening_attempts')->cascadeOnDelete();
            $table->unsignedBigInteger('listening_attempt_evaluation_id')->nullable();
            $table->unsignedBigInteger('listening_attempt_answer_evaluation_id')->nullable();
            $table->foreignId('listening_question_id')->nullable()->constrained('listening_questions')->restrictOnDelete();
            $table->foreignId('listening_section_id')->nullable()->constrained('listening_sections')->restrictOnDelete();
            $table->unsignedBigInteger('listening_transcript_id')->nullable();
            $table->unsignedSmallInteger('question_number')->index();
            $table->unsignedTinyInteger('section_number')->index();
            $table->string('question_type', 50)->index();
            $table->json('student_answer_snapshot')->nullable();
            $table->json('correct_answer_snapshot')->nullable();
            $table->json('accepted_answers_snapshot')->nullable();
            $table->json('normalized_answer_snapshot')->nullable();
            $table->string('match_status', 30)->nullable()->index();
            $table->string('match_reason')->nullable();
            $table->decimal('marks_awarded', 5, 2)->default(0);
            $table->decimal('marks_available', 5, 2)->default(1);
            $table->unsignedInteger('transcript_line_start')->nullable();
            $table->unsignedInteger('transcript_line_end')->nullable();
            $table->longText('transcript_text_snippet')->nullable();
            $table->json('highlighted_transcript')->nullable();
            $table->decimal('audio_timestamp_start', 10, 2)->nullable();
            $table->decimal('audio_timestamp_end', 10, 2)->nullable();
            $table->longText('explanation')->nullable();
            $table->json('visibility_meta')->nullable();
            $table->json('admin_meta')->nullable();
            $table->timestamps();

            $table->foreign('listening_attempt_evaluation_id', 'lri_eval_fk')
                ->references('id')->on('listening_attempt_evaluations')->nullOnDelete();
            $table->foreign('listening_attempt_answer_evaluation_id', 'lri_answer_eval_fk')
                ->references('id')->on('listening_attempt_answer_evaluations')->nullOnDelete();
            $table->foreign('listening_transcript_id', 'lri_transcript_fk')
                ->references('id')->on('listening_transcripts')->restrictOnDelete();

            $table->index('listening_result_id');
            $table->index('listening_attempt_id');
            $table->index('listening_transcript_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_review_items');
    }
};
