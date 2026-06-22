<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('course_category_id')->nullable()->constrained('course_categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('exam_type', 30)->default('academic');
            $table->string('level', 30)->default('intermediate');
            $table->string('thumbnail_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_category_id');
            $table->index(['status', 'exam_type']);
            $table->index('sort_order');
            $table->index('published_at');
            $table->index('deleted_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
