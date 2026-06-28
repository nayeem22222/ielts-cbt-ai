<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderListeningQuestionGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('listeningTest');
        $section = $this->route('section');

        return $test instanceof ListeningTest
            && $section instanceof ListeningSection
            && ($this->user()?->can('create', [ListeningQuestionGroup::class, $test, $section]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ListeningSection $section */
        $section = $this->route('section');

        return [
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => [
                'integer',
                Rule::exists('listening_question_groups', 'id')->where('listening_section_id', $section->id),
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
