<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class PlanLabellingQuestionTypeService extends MapLabellingQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::PlanLabelling;
    }

    public function label(): string
    {
        return 'Plan Labelling';
    }

    public function schema(): array
    {
        $schema = parent::schema();
        $schema['default_layout'] = ListeningLayoutType::Diagram->value;

        return $schema;
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload = parent::normalizePayload($payload, $group, $question);
        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Diagram->value;

        return $payload;
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        $data = parent::buildPreviewData($group, $questions);
        $data['type'] = $this->type()->value;

        return $data;
    }
}
