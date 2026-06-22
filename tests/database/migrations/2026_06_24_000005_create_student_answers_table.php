<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_question_id')->nullable()->constrained('test_question')->nullOnDelete();
            $table->string('module', 20);
            $table->longText('answer_text')->nullable();
            $table->json('selected_options')->nullable();
            $table->string('audio_path')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_final')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['test_attempt_id', 'question_id']);
            $table->index(['test_attempt_id', 'module']);
            $table->index(['test_section_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
