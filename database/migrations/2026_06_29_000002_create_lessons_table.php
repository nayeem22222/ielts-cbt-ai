<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('course_section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('content_type', 30)->default('video');
            $table->string('video_url')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_section_id', 'slug']);
            $table->index(['course_section_id', 'sort_order']);
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
