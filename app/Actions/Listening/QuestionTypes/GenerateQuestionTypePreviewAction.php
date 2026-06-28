<?php

declare(strict_types=1);

namespace App\Actions\Listening\QuestionTypes;

use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GenerateQuestionTypePreviewAction
{
    public function __construct(
        private readonly ListeningQuestionTypeRegistry $registry,
    ) {}

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningQuestion>|EloquentCollection<int, \App\Models\Listening\ListeningQuestion>  $questions
     * @return array<string, mixed>
     */
    public function execute(ListeningQuestionGroup $group, Collection|EloquentCollection $questions): array
    {
        $type = $group->question_type;

        if ($type === null || ! $this->registry->isEnabled($type)) {
            return [
                'type' => $type?->value,
                'instruction' => $group->instruction,
                'error' => 'Unsupported question type.',
            ];
        }

        if (! $questions instanceof EloquentCollection) {
            $questions = new EloquentCollection($questions->all());
        }

        $preview = $this->registry->serviceFor($type)->buildPreviewData($group, $questions);
        $preview['preview_partial'] = $this->registry->previewPartialFor($type);

        return $preview;
    }
}
