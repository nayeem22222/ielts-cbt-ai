<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('full_mock');
            $table->string('exam_type', 30)->default('academic');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->boolean('is_timed')->default(true);
            $table->decimal('passing_band', 2, 1)->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'exam_type', 'status']);
            $table->index('published_at');
            $table->index('deleted_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
