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
            $table->string('processed_path')->nullable()->after('path');
            $table->string('normalized_path')->nullable()->after('processed_path');
            $table->string('preview_waveform_path')->nullable()->after('waveform_json_path');
            $table->json('peaks')->nullable()->after('preview_waveform_path');
            $table->decimal('loudness_lufs', 8, 2)->nullable()->after('peaks');
            $table->decimal('peak_db', 8, 2)->nullable()->after('loudness_lufs');
            $table->json('silence_report')->nullable()->after('peak_db');
            $table->timestamp('processing_started_at')->nullable()->after('validation_errors');
            $table->timestamp('processing_finished_at')->nullable()->after('processing_started_at');
            $table->longText('processing_error')->nullable()->after('processing_finished_at');
            $table->unsignedInteger('retry_count')->default(0)->after('processing_error');
        });
    }

    public function down(): void
    {
        Schema::table('listening_audios', function (Blueprint $table): void {
            $table->dropColumn([
                'processed_path',
                'normalized_path',
                'preview_waveform_path',
                'peaks',
                'loudness_lufs',
                'peak_db',
                'silence_report',
                'processing_started_at',
                'processing_finished_at',
                'processing_error',
                'retry_count',
            ]);
        });
    }
};
