<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Http\Requests\Admin\CourseSlugRequest;
use App\Models\Listening\ListeningTest;
use Illuminate\Validation\Rule;

class StoreListeningTestRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ListeningTest::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('listening_tests', 'slug')],
            'test_code' => ['nullable', 'string', 'max:100', Rule::unique('listening_tests', 'test_code')],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(ListeningTestStatus::values())],
            'test_type' => ['required', 'string', Rule::in(ListeningTestType::values())],
            'difficulty_level' => ['required', 'string', Rule::in(ListeningDifficultyLevel::values())],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:180'],
            'transfer_time_minutes' => ['nullable', 'integer', 'min:0', 'max:30'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
