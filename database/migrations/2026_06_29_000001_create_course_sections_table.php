<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'sort_order']);
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
