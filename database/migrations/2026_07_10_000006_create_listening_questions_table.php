<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_questions')) {
            Schema::create('listening_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_test_id')->constrained('listening_tests')->cascadeOnDelete();
                $table->foreignId('listening_section_id')->constrained('listening_sections')->cascadeOnDelete();
                $table->foreignId('listening_question_group_id')->constrained('listening_question_groups')->cascadeOnDelete();
                $table->unsignedTinyInteger('question_number');
                $table->string('question_type', 50);
                $table->longText('question_text')->nullable();
                $table->longText('question_html')->nullable();
                $table->text('instruction')->nullable();
                $table->json('options')->nullable();
                $table->json('correct_answer')->nullable();
                $table->json('accepted_answers')->nullable();
                $table->string('answer_format', 20)->default('text');
                $table->unsignedTinyInteger('word_limit')->nullable();
                $table->boolean('case_sensitive')->default(false);
                $table->boolean('order_sensitive')->default(false);
                $table->boolean('allow_plural')->default(true);
                $table->boolean('allow_articles')->default(true);
                $table->boolean('allow_punctuation_variation')->default(true);
                $table->decimal('marks', 5, 2)->default(1);
                $table->longText('explanation')->nullable();
                $table->json('transcript_location')->nullable();
                $table->decimal('audio_timestamp_start', 10, 3)->nullable();
                $table->decimal('audio_timestamp_end', 10, 3)->nullable();
                $table->unsignedSmallInteger('display_order')->default(1);
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['listening_test_id', 'question_number']);
                $table->index('listening_test_id');
                $table->index('listening_section_id');
                $table->index('listening_question_group_id');
                $table->index('question_number');
                $table->index('question_type');
                $table->index('display_order');
                $table->index(['listening_section_id', 'display_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_questions');
    }
};
