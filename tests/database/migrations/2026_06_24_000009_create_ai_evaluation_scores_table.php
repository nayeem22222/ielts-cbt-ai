<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('overall_band', 2, 1)->nullable();
            $table->json('criteria')->nullable();
            $table->json('raw_scores')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique('ai_response_id');
            $table->index(['student_answer_id', 'overall_band']);
            $table->index(['requires_review', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluation_scores');
    }
};
