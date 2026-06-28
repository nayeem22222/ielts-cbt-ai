<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningQuestionType;
use App\Http\Requests\Admin\Listening\Concerns\DecodesListeningJsonAttributes;
use App\Http\Requests\Admin\Listening\Concerns\ValidatesListeningQuestionTypePayload;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Rules\Listening\ValidListeningAcceptedAnswers;
use App\Rules\Listening\ValidListeningQuestionNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListeningQuestionRequest extends FormRequest
{
    use DecodesListeningJsonAttributes;
    use ValidatesListeningQuestionTypePayload;

    public function authorize(): bool
    {
        /** @var ListeningQuestion|null $question */
        $question = $this->route('question');

        return $question !== null && ($this->user()?->can('update', $question) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableIntegers(['word_limit', 'display_order']);
        $this->decodeJsonAttributes(['options', 'correct_answer', 'accepted_answers', 'transcript_location', 'meta']);
        $this->merge([
            'case_sensitive' => $this->boolean('case_sensitive'),
            'order_sensitive' => $this->boolean('order_sensitive'),
            'allow_plural' => $this->boolean('allow_plural', true),
            'allow_articles' => $this->boolean('allow_articles', true),
            'allow_punctuation_variation' => $this->boolean('allow_punctuation_variation', true),
            'is_required' => $this->boolean('is_required', true),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ListeningTest $test */
        $test = $this->route('listeningTest');
        /** @var ListeningSection $section */
        $section = $this->route('section');
        /** @var ListeningQuestionGroup $group */
        $group = $this->route('group');
        /** @var ListeningQuestion $question */
        $question = $this->route('question');
        $allowDraft = (bool) config('listening.questions.allow_draft_without_answer', true);

        return [
            'question_number' => ['required', 'integer', 'min:1', 'max:40', new ValidListeningQuestionNumber($test, $section, $group, $question->id)],
            'question_type' => ['required', 'string', Rule::enum(ListeningQuestionType::class)],
            'question_text' => ['nullable', 'string'],
            'question_html' => ['nullable', 'string'],
            'instruction' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'correct_answer' => [$allowDraft ? 'nullable' : 'required', 'array'],
            'accepted_answers' => ['nullable', 'array', new ValidListeningAcceptedAnswers],
            'answer_format' => ['required', 'string', Rule::enum(ListeningAnswerFormat::class)],
            'word_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'case_sensitive' => ['boolean'],
            'order_sensitive' => ['boolean'],
            'allow_plural' => ['boolean'],
            'allow_articles' => ['boolean'],
            'allow_punctuation_variation' => ['boolean'],
            'marks' => ['required', 'numeric', 'min:0', 'max:5'],
            'explanation' => ['nullable', 'string'],
            'transcript_location' => ['nullable', 'array'],
            'audio_timestamp_start' => ['nullable', 'numeric', 'min:0'],
            'audio_timestamp_end' => ['nullable', 'numeric', 'min:0', 'gte:audio_timestamp_start'],
            'display_order' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->validateQuestionTypePayload($validator, 'question');
    }
}
