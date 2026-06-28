<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Foundation\Http\FormRequest;

class BulkCreateListeningQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningQuestionGroup|null $group */
        $group = $this->route('group');

        return $group !== null && ($this->user()?->can('bulkCreate', [ListeningQuestion::class, $group]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
