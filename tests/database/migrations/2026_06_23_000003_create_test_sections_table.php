<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedSmallInteger('question_count')->default(0);
            $table->decimal('total_marks', 6, 2)->default(0);
            $table->text('stimulus_text')->nullable();
            $table->string('stimulus_audio_path')->nullable();
            $table->string('stimulus_image_path')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['test_module_id', 'sort_order']);
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_sections');
    }
};
