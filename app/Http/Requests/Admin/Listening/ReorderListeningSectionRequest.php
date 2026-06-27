<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderListeningSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningTest|null $test */
        $test = $this->route('listeningTest');

        return $test !== null && ($this->user()?->can('reorder', [ListeningSection::class, $test]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array', 'min:1', 'max:4'],
            'sections.*' => ['required', 'integer', 'distinct', 'exists:listening_sections,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ListeningTest $test */
            $test = $this->route('listeningTest');
            $ids = array_map('intval', $this->input('sections', []));

            if ($ids === []) {
                return;
            }

            $belongingCount = $test->sections()->whereIn('id', $ids)->count();

            if ($belongingCount !== count($ids)) {
                $validator->errors()->add('sections', 'One or more sections do not belong to this listening test.');
            }
        });
    }
}
