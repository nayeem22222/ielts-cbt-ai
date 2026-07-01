<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listening_attempt_evaluations')) {
            return;
        }

        Schema::create('listening_attempt_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listening_attempt_id')->constrained('listening_attempts')->cascadeOnDelete();
            $table->foreignId('listening_test_id')->constrained('listening_tests')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('evaluation_version', 20);
            $table->string('status', 30)->default('processing');
            $table->decimal('raw_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('total_questions')->default(40);
            $table->decimal('total_correct', 5, 2)->default(0);
            $table->decimal('band_score', 3, 1)->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evaluation_type', 30)->default('system');
            $table->json('summary')->nullable();
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('listening_attempt_id');
            $table->index('listening_test_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('evaluation_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_attempt_evaluations');
    }
};
