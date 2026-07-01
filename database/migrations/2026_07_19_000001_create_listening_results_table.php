<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listening_results')) {
            return;
        }

        Schema::create('listening_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listening_attempt_id')->constrained('listening_attempts')->cascadeOnDelete();
            $table->unsignedBigInteger('listening_attempt_evaluation_id')->nullable();
            $table->foreignId('listening_test_id')->constrained('listening_tests')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('result_code', 30)->nullable()->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('raw_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('total_questions')->default(40);
            $table->decimal('total_correct', 5, 2)->default(0);
            $table->decimal('total_incorrect', 5, 2)->default(0);
            $table->unsignedSmallInteger('total_unanswered')->default(0);
            $table->decimal('band_score', 3, 1)->nullable()->index();
            $table->unsignedInteger('listening_duration_seconds')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_visible_to_student')->default(true)->index();
            $table->json('section_breakdown')->nullable();
            $table->json('question_type_breakdown')->nullable();
            $table->json('question_summary')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('listening_attempt_evaluation_id', 'lr_eval_fk')
                ->references('id')->on('listening_attempt_evaluations')->restrictOnDelete();

            $table->index('listening_attempt_id');
            $table->index('listening_test_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_results');
    }
};
