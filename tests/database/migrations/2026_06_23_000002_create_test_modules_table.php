<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedSmallInteger('question_count')->default(0);
            $table->decimal('total_marks', 6, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['test_id', 'module']);
            $table->index(['test_id', 'sort_order']);
            $table->index('module');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_modules');
    }
};
