<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_question_groups')) {
            Schema::create('listening_question_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_test_id')->constrained('listening_tests')->cascadeOnDelete();
                $table->foreignId('listening_section_id')->constrained('listening_sections')->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->longText('instruction')->nullable();
                $table->string('question_type', 50);
                $table->unsignedTinyInteger('start_question_number');
                $table->unsignedTinyInteger('end_question_number');
                $table->unsignedTinyInteger('total_questions');
                $table->unsignedTinyInteger('display_order')->default(1);
                $table->string('layout_type', 30)->default('default');
                $table->foreignId('audio_id')->nullable()->constrained('listening_audios')->nullOnDelete();
                $table->json('transcript_reference')->nullable();
                $table->string('image_path')->nullable();
                $table->string('image_alt')->nullable();
                $table->longText('content')->nullable();
                $table->json('options')->nullable();
                $table->json('settings')->nullable();
                $table->json('validation_rules')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('listening_test_id');
                $table->index('listening_section_id');
                $table->index('question_type');
                $table->index('display_order');
                $table->index('audio_id');
                $table->index(['start_question_number', 'end_question_number'], 'listening_groups_question_range_idx');
                $table->index(['listening_test_id', 'start_question_number', 'end_question_number'], 'listening_groups_test_question_range_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_question_groups');
    }
};
