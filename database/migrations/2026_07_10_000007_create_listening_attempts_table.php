<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_attempts')) {
            Schema::create('listening_attempts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('listening_test_id')->constrained('listening_tests')->restrictOnDelete();
                $table->string('status', 20)->default('not_started');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedTinyInteger('total_questions')->default(40);
                $table->unsignedTinyInteger('total_answered')->default(0);
                $table->unsignedTinyInteger('total_correct')->default(0);
                $table->unsignedTinyInteger('raw_score')->default(0);
                $table->decimal('band_score', 2, 1)->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->unsignedInteger('remaining_seconds')->nullable();
                $table->unsignedTinyInteger('current_section_number')->nullable();
                $table->unsignedTinyInteger('current_question_number')->nullable();
                $table->json('browser_info')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('device_info')->nullable();
                $table->json('security_flags')->nullable();
                $table->json('result_meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('user_id');
                $table->index('listening_test_id');
                $table->index('status');
                $table->index('started_at');
                $table->index('submitted_at');
                $table->index(['user_id', 'listening_test_id', 'status']);
                $table->index(['listening_test_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_attempts');
    }
};
