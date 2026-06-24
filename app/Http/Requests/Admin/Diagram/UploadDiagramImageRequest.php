<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Diagram;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingQuestionGroup;
use Illuminate\Validation\Rule;

class UploadDiagramImageRequest extends DiagramScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->groupFromRoute();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'diagram_image' => $this->imageFileRules(),
        ];
    }
}
