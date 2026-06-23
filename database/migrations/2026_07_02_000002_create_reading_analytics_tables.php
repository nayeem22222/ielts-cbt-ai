<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reading_question_timings')) {
            Schema::create('reading_question_timings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('question_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('time_spent_seconds')->default(0);
                $table->unsignedSmallInteger('visit_count')->default(0);
                $table->timestamp('first_viewed_at')->nullable();
                $table->timestamp('last_viewed_at')->nullable();
                $table->timestamps();

                $table->unique(['test_attempt_id', 'question_id']);
                $table->index(['test_attempt_id', 'time_spent_seconds'], 'rq_timings_attempt_time_idx');
            });
        } else {
            Schema::table('reading_question_timings', function (Blueprint $table): void {
                if (! $this->indexExists('reading_question_timings', 'rq_timings_attempt_time_idx')) {
                    $table->index(['test_attempt_id', 'time_spent_seconds'], 'rq_timings_attempt_time_idx');
                }
            });
        }

        if (! Schema::hasTable('reading_analytics')) {
            Schema::create('reading_analytics', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('band', 2, 1)->nullable();
                $table->decimal('accuracy_percent', 5, 2)->default(0);
                $table->unsignedInteger('average_time_seconds')->default(0);
                $table->unsignedSmallInteger('skipped_count')->default(0);
                $table->unsignedSmallInteger('total_questions')->default(0);
                $table->json('time_per_question')->nullable();
                $table->json('heat_map')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique('test_attempt_id');
                $table->index(['test_id', 'computed_at']);
                $table->index(['user_id', 'computed_at']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'select 1 from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index]
        );

        return $result !== [];
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_analytics');
        Schema::dropIfExists('reading_question_timings');
    }
};
