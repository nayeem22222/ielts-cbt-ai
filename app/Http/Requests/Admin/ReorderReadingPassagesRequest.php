<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderReadingPassagesRequest extends FormRequest
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
        /** @var ReadingTest $test */
        $test = $this->route('readingTest');

        return [
            'passage_ids' => ['required', 'array', 'min:1'],
            'passage_ids.*' => [
                'integer',
                Rule::exists('reading_passages', 'id')->where('reading_test_id', $test->id),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map('intval', $this->input('passage_ids', []));
    }
}
