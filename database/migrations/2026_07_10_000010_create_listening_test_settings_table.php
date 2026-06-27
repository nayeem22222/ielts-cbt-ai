<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_test_settings')) {
            Schema::create('listening_test_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listening_test_id')->unique()->constrained('listening_tests')->cascadeOnDelete();
                $table->boolean('allow_review_after_submit')->default(true);
                $table->boolean('show_correct_answer')->default(true);
                $table->boolean('show_transcript_after_submit')->default(false);
                $table->boolean('show_audio_review')->default(false);
                $table->boolean('allow_audio_replay')->default(false);
                $table->boolean('allow_audio_seek')->default(false);
                $table->boolean('auto_submit_on_timer_end')->default(true);
                $table->boolean('enable_tab_switch_detection')->default(true);
                $table->boolean('enable_copy_protection')->default(true);
                $table->boolean('enable_question_flagging')->default(true);
                $table->boolean('enable_auto_save')->default(true);
                $table->unsignedSmallInteger('auto_save_interval_seconds')->default(10);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_test_settings');
    }
};
