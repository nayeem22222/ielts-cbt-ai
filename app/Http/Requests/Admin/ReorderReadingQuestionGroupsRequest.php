<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ReadingPassage;
use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderReadingQuestionGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('readingTest');

        return $test instanceof ReadingTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ReadingPassage $passage */
        $passage = $this->route('passage');

        return [
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => [
                'integer',
                Rule::exists('reading_question_groups', 'id')->where('passage_id', $passage->id),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map('intval', $this->input('group_ids', []));
    }
}
