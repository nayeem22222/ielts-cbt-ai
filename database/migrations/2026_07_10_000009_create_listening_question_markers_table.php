<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_question_markers')) {
            Schema::create('listening_question_markers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_test_id')->constrained('listening_tests')->cascadeOnDelete();
                $table->foreignId('listening_section_id')->constrained('listening_sections')->cascadeOnDelete();
                $table->foreignId('listening_question_id')->nullable()->constrained('listening_questions')->cascadeOnDelete();
                $table->foreignId('listening_question_group_id')->nullable()->constrained('listening_question_groups')->cascadeOnDelete();
                $table->string('marker_type', 30);
                $table->decimal('timestamp_start', 10, 3);
                $table->decimal('timestamp_end', 10, 3)->nullable();
                $table->string('label')->nullable();
                $table->text('note')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index('listening_test_id');
                $table->index('listening_section_id');
                $table->index('listening_question_id');
                $table->index('listening_question_group_id');
                $table->index('marker_type');
                $table->index('timestamp_start');
                $table->index(['listening_section_id', 'timestamp_start'], 'listening_markers_section_timestamp_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_question_markers');
    }
};
