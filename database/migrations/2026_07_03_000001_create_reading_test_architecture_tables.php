<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reading_tests')) {
            Schema::create('reading_tests', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('exam_type', 30)->default('academic');
                $table->unsignedSmallInteger('duration_minutes')->default(60);
                $table->text('instructions')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['exam_type', 'status']);
                $table->index(['status', 'published_at']);
                $table->index('created_by');
                $table->index('deleted_at');
            });
        }

        if (! Schema::hasTable('reading_passages')) {
            Schema::create('reading_passages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reading_test_id')->constrained('reading_tests')->cascadeOnDelete();
                $table->unsignedSmallInteger('part_number');
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->text('instruction')->nullable();
                $table->longText('content_html')->nullable();
                $table->longText('content_text')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(1);
                $table->timestamps();

                $table->index('reading_test_id');
                $table->index(['reading_test_id', 'part_number']);
                $table->index(['reading_test_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('reading_question_groups')) {
            Schema::create('reading_question_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('passage_id')->constrained('reading_passages')->cascadeOnDelete();
                $table->string('title');
                $table->text('instruction')->nullable();
                $table->string('question_type', 50);
                $table->unsignedSmallInteger('start_question')->nullable();
                $table->unsignedSmallInteger('end_question')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(1);
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->index('passage_id');
                $table->index(['passage_id', 'question_type']);
                $table->index(['passage_id', 'sort_order']);
                $table->index(['start_question', 'end_question']);
            });
        }

        if (! Schema::hasTable('reading_questions')) {
            Schema::create('reading_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('group_id')->constrained('reading_question_groups')->cascadeOnDelete();
                $table->unsignedSmallInteger('question_number');
                $table->text('prompt')->nullable();
                $table->string('paragraph_reference', 30)->nullable();
                $table->text('explanation')->nullable();
                $table->decimal('marks', 5, 2)->default(1);
                $table->unsignedSmallInteger('sort_order')->default(1);
                $table->string('difficulty', 20)->default('medium');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('group_id');
                $table->index('question_number');
                $table->index(['group_id', 'question_number']);
                $table->index(['group_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('reading_question_options')) {
            Schema::create('reading_question_options', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('question_id')->constrained('reading_questions')->cascadeOnDelete();
                $table->string('option_key', 50);
                $table->text('option_label');
                $table->unsignedSmallInteger('sort_order')->default(1);
                $table->timestamps();

                $table->index('question_id');
                $table->index(['question_id', 'option_key']);
                $table->index(['question_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('reading_correct_answers')) {
            Schema::create('reading_correct_answers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('question_id')->constrained('reading_questions')->cascadeOnDelete();
                $table->text('answer')->nullable();
                $table->json('answer_json')->nullable();
                $table->string('matching_key', 100)->nullable();
                $table->timestamps();

                $table->index('question_id');
                $table->index(['question_id', 'matching_key']);
            });
        }

        if (! Schema::hasTable('reading_attempts')) {
            Schema::create('reading_attempts', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reading_test_id')->constrained('reading_tests')->restrictOnDelete();
                $table->string('status', 20)->default('not_started');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('remaining_seconds')->nullable();
                $table->foreignId('current_passage_id')->nullable()->constrained('reading_passages')->nullOnDelete();
                $table->foreignId('current_question_id')->nullable()->constrained('reading_questions')->nullOnDelete();
                $table->decimal('score', 6, 2)->nullable();
                $table->decimal('band', 2, 1)->nullable();
                $table->unsignedInteger('time_spent')->default(0);
                $table->json('navigation_state')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('reading_test_id');
                $table->index('status');
                $table->index(['user_id', 'reading_test_id', 'status']);
                $table->index(['reading_test_id', 'status']);
                $table->index('submitted_at');
            });
        }

        if (! Schema::hasTable('reading_answers')) {
            Schema::create('reading_answers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('attempt_id')->constrained('reading_attempts')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('reading_questions')->restrictOnDelete();
                $table->text('answer')->nullable();
                $table->json('answer_json')->nullable();
                $table->boolean('flagged')->default(false);
                $table->boolean('is_correct')->nullable();
                $table->timestamp('answered_at')->nullable();
                $table->json('state')->nullable();
                $table->timestamps();

                $table->unique(['attempt_id', 'question_id']);
                $table->index('attempt_id');
                $table->index('question_id');
                $table->index(['attempt_id', 'flagged']);
                $table->index(['attempt_id', 'is_correct']);
                $table->index('answered_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_answers');
        Schema::dropIfExists('reading_attempts');
        Schema::dropIfExists('reading_correct_answers');
        Schema::dropIfExists('reading_question_options');
        Schema::dropIfExists('reading_questions');
        Schema::dropIfExists('reading_question_groups');
        Schema::dropIfExists('reading_passages');
        Schema::dropIfExists('reading_tests');
    }
};
