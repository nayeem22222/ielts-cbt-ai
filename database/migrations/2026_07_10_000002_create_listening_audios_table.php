<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_audios')) {
            Schema::create('listening_audios', function (Blueprint $table): void {
                $table->id();
                $table->string('original_name');
                $table->string('stored_name');
                $table->string('disk', 50)->default('public');
                $table->string('path');
                $table->string('url')->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->string('extension', 20)->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->unsignedInteger('bitrate')->nullable();
                $table->unsignedInteger('sample_rate')->nullable();
                $table->unsignedTinyInteger('channels')->nullable();
                $table->string('format', 30)->nullable();
                $table->string('waveform_path')->nullable();
                $table->string('waveform_json_path')->nullable();
                $table->string('processing_status', 20)->default('pending');
                $table->string('validation_status', 20)->default('pending');
                $table->json('validation_errors')->nullable();
                $table->string('checksum', 64)->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('processing_status');
                $table->index('validation_status');
                $table->index('uploaded_by');
                $table->index('checksum');
                $table->index(['processing_status', 'validation_status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_audios');
    }
};
