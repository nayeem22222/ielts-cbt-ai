<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('file_type', 30)->default('pdf');
            $table->string('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_downloadable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'sort_order']);
            $table->index(['lesson_id', 'sort_order']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
