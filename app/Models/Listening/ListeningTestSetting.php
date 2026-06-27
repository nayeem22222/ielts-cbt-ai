<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningTestSetting extends Model
{
    protected $fillable = [
        'listening_test_id',
        'allow_review_after_submit',
        'show_correct_answer',
        'show_transcript_after_submit',
        'show_audio_review',
        'allow_audio_replay',
        'allow_audio_seek',
        'auto_submit_on_timer_end',
        'enable_tab_switch_detection',
        'enable_copy_protection',
        'enable_question_flagging',
        'enable_auto_save',
        'auto_save_interval_seconds',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'allow_review_after_submit' => 'boolean',
            'show_correct_answer' => 'boolean',
            'show_transcript_after_submit' => 'boolean',
            'show_audio_review' => 'boolean',
            'allow_audio_replay' => 'boolean',
            'allow_audio_seek' => 'boolean',
            'auto_submit_on_timer_end' => 'boolean',
            'enable_tab_switch_detection' => 'boolean',
            'enable_copy_protection' => 'boolean',
            'enable_question_flagging' => 'boolean',
            'enable_auto_save' => 'boolean',
            'auto_save_interval_seconds' => 'integer',
            'settings' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    /**
     * @return array<string, mixed>
     */
    public static function officialDefaults(): array
    {
        return [
            'allow_review_after_submit' => true,
            'show_correct_answer' => true,
            'show_transcript_after_submit' => false,
            'show_audio_review' => false,
            'allow_audio_replay' => false,
            'allow_audio_seek' => false,
            'auto_submit_on_timer_end' => true,
            'enable_tab_switch_detection' => true,
            'enable_copy_protection' => true,
            'enable_question_flagging' => true,
            'enable_auto_save' => true,
            'auto_save_interval_seconds' => ListeningConstants::DEFAULT_AUTO_SAVE_INTERVAL_SECONDS,
        ];
    }
}
