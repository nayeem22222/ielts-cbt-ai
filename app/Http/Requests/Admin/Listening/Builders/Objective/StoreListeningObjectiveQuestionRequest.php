<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening\Builders\Objective;

use App\Enums\Listening\ListeningQuestionType;
use App\Http\Requests\Admin\Listening\Builders\Concerns\AuthorizesListeningBuilder;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Foundation\Http\FormRequest;

class StoreListeningObjectiveQuestionRequest extends FormRequest
{
    use AuthorizesListeningBuilder;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ListeningQuestionGroup|null $group */
        $group = $this->route('group');
        $optionsMin = $group?->question_type === ListeningQuestionType::MCQ ? 3 : 2;

        return [
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => ['nullable', 'string', 'max:50'],
            'correct_answers' => ['nullable', 'array', 'min:1'],
            'correct_answers.*' => ['string', 'max:50'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
            'options' => ['nullable', 'array', "min:{$optionsMin}"],
            'options.*.option_key' => ['nullable', 'string', 'max:50'],
            'options.*.option_label' => ['required_with:options', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionAttributes(): array
    {
        return $this->only([
            'question_number',
            'prompt',
            'correct_answer',
            'correct_answers',
            'explanation',
            'difficulty',
            'options',
        ]);
    }
}
