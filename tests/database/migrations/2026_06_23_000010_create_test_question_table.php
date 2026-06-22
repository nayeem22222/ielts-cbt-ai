<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('marks', 5, 2)->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['test_section_id', 'question_id']);
            $table->index(['test_id', 'sort_order']);
            $table->index(['test_module_id', 'sort_order']);
            $table->index(['test_section_id', 'sort_order']);
            $table->index('question_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_question');
    }
};
