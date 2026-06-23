<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_question_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question_type', 50);
            $table->unsignedSmallInteger('question_number')->nullable();
            $table->text('student_response')->nullable();
            $table->text('expected_response')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('score_awarded', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(1);
            $table->decimal('partial_ratio', 5, 4)->default(0);
            $table->string('feedback', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['result_id', 'question_id']);
            $table->index(['result_id', 'is_correct']);
            $table->index(['question_type', 'is_correct']);
        });

        Schema::create('result_statistics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('answered_count')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('incorrect_count')->default(0);
            $table->unsignedSmallInteger('unanswered_count')->default(0);
            $table->unsignedSmallInteger('flagged_count')->default(0);
            $table->unsignedSmallInteger('partial_count')->default(0);
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->decimal('accuracy_percent', 5, 2)->default(0);
            $table->json('by_question_type')->nullable();
            $table->json('by_passage')->nullable();
            $table->timestamps();

            $table->unique('result_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_statistics');
        Schema::dropIfExists('result_question_scores');
    }
};
