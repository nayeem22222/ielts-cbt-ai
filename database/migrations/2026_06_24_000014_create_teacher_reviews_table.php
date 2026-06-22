<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_evaluation_score_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('original_band', 2, 1)->nullable();
            $table->decimal('adjusted_band', 2, 1)->nullable();
            $table->longText('feedback')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status', 'created_at']);
            $table->index('student_answer_id');
            $table->index('ai_evaluation_score_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_reviews');
    }
};
