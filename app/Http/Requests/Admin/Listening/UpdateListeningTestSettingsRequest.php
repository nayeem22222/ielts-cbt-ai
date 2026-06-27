<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningTest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateListeningTestSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningTest|null $test */
        $test = $this->route('listeningTest');

        return $test !== null && ($this->user()?->can('update', $test) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_review_after_submit' => $this->boolean('allow_review_after_submit'),
            'show_correct_answer' => $this->boolean('show_correct_answer'),
            'show_transcript_after_submit' => $this->boolean('show_transcript_after_submit'),
            'show_audio_review' => $this->boolean('show_audio_review'),
            'allow_audio_replay' => $this->boolean('allow_audio_replay'),
            'allow_audio_seek' => $this->boolean('allow_audio_seek'),
            'auto_submit_on_timer_end' => $this->boolean('auto_submit_on_timer_end'),
            'enable_tab_switch_detection' => $this->boolean('enable_tab_switch_detection'),
            'enable_copy_protection' => $this->boolean('enable_copy_protection'),
            'enable_question_flagging' => $this->boolean('enable_question_flagging'),
            'enable_auto_save' => $this->boolean('enable_auto_save'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'allow_review_after_submit' => ['boolean'],
            'show_correct_answer' => ['boolean'],
            'show_transcript_after_submit' => ['boolean'],
            'show_audio_review' => ['boolean'],
            'allow_audio_replay' => ['boolean'],
            'allow_audio_seek' => ['boolean'],
            'auto_submit_on_timer_end' => ['boolean'],
            'enable_tab_switch_detection' => ['boolean'],
            'enable_copy_protection' => ['boolean'],
            'enable_question_flagging' => ['boolean'],
            'enable_auto_save' => ['boolean'],
            'auto_save_interval_seconds' => ['required', 'integer', 'min:5', 'max:120'],
        ];
    }
}
