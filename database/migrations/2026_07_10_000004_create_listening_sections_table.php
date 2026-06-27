<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_sections')) {
            Schema::create('listening_sections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_test_id')->constrained('listening_tests')->cascadeOnDelete();
                $table->unsignedTinyInteger('section_number');
                $table->string('title')->nullable();
                $table->longText('instruction')->nullable();
                $table->string('section_type', 30)->default('conversation');
                $table->foreignId('audio_id')->nullable()->constrained('listening_audios')->nullOnDelete();
                $table->foreignId('transcript_id')->nullable()->constrained('listening_transcripts')->nullOnDelete();
                $table->unsignedTinyInteger('total_questions')->default(10);
                $table->unsignedTinyInteger('start_question_number');
                $table->unsignedTinyInteger('end_question_number');
                $table->unsignedTinyInteger('display_order')->default(1);
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->unsignedSmallInteger('preparation_seconds')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['listening_test_id', 'section_number']);
                $table->index('listening_test_id');
                $table->index('section_number');
                $table->index('display_order');
                $table->index('audio_id');
                $table->index('transcript_id');
                $table->index(['listening_test_id', 'display_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_sections');
    }
};
