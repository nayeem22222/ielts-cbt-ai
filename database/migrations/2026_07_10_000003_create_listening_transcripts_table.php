<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_transcripts')) {
            Schema::create('listening_transcripts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_audio_id')->nullable()->constrained('listening_audios')->nullOnDelete();
                $table->string('title')->nullable();
                $table->longText('transcript_text');
                $table->longText('formatted_transcript')->nullable();
                $table->json('timestamped_transcript')->nullable();
                $table->string('language', 10)->default('en');
                $table->string('visibility', 20)->default('hidden');
                $table->boolean('is_official')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('listening_audio_id');
                $table->index('visibility');
                $table->index('is_official');
                $table->index('created_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_transcripts');
    }
};
