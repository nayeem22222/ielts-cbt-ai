<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class NoteCompletionQuestionTypeService extends FormCompletionQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::NoteCompletion;
    }

    public function label(): string
    {
        return 'Note Completion';
    }

    public function schema(): array
    {
        $schema = parent::schema();
        $schema['default_layout'] = ListeningLayoutType::Default->value;

        return $schema;
    }

    public function defaultSettings(): array
    {
        return $this->normalizeCompletionSettings([
            'allow_bullets' => true,
        ], 'notes', 2);
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload = parent::normalizePayload($payload, $group, $question);
        $payload['settings'] = $this->normalizeCompletionSettings(
            is_array($payload['settings'] ?? null) ? $payload['settings'] : [],
            'notes',
        );
        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Default->value;

        return $payload;
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        $data = parent::buildPreviewData($group, $questions);
        $data['type'] = $this->type()->value;

        return $data;
    }
}
