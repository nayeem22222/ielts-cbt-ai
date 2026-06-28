<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listening_audios', function (Blueprint $table): void {
            if (! Schema::hasColumn('listening_audios', 'last_processed_at')) {
                $table->timestamp('last_processed_at')->nullable()->after('processing_finished_at');
            }

            if (! Schema::hasColumn('listening_audios', 'pipeline_version')) {
                $table->string('pipeline_version', 20)->nullable()->after('last_processed_at');
            }

            if (! Schema::hasColumn('listening_audios', 'processing_locked_at')) {
                $table->timestamp('processing_locked_at')->nullable()->after('pipeline_version');
            }

            if (! Schema::hasColumn('listening_audios', 'processing_lock_token')) {
                $table->string('processing_lock_token', 64)->nullable()->after('processing_locked_at');
            }
        });

        if (! Schema::hasTable('listening_audio_processing_logs')) {
            Schema::create('listening_audio_processing_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_audio_id')
                    ->constrained('listening_audios')
                    ->cascadeOnDelete();
                $table->string('job_id')->nullable();
                $table->string('stage', 50)->index();
                $table->string('status', 20)->default('started')->index();
                $table->text('message')->nullable();
                $table->json('context')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index('listening_audio_id');
                $table->index('created_at');
                $table->index(['listening_audio_id', 'stage']);
                $table->index(['listening_audio_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_audio_processing_logs');

        Schema::table('listening_audios', function (Blueprint $table): void {
            $table->dropColumn(array_filter([
                Schema::hasColumn('listening_audios', 'last_processed_at') ? 'last_processed_at' : null,
                Schema::hasColumn('listening_audios', 'pipeline_version') ? 'pipeline_version' : null,
                Schema::hasColumn('listening_audios', 'processing_locked_at') ? 'processing_locked_at' : null,
                Schema::hasColumn('listening_audios', 'processing_lock_token') ? 'processing_lock_token' : null,
            ]));
        });
    }
};
