<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Enums\Course\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\ReadingQuestionGroup;
use App\Services\Admin\Exam\ReadingDiagramQuestionService;
use App\Services\Exam\ReadingTestRendererService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingTestDiagramController extends Controller
{
    public function __construct(
        private readonly ReadingDiagramQuestionService $diagram,
        private readonly ReadingTestRendererService $renderer,
    ) {
    }

    public function showImage(ReadingQuestionGroup $group): StreamedResponse
    {
        $group->loadMissing('passage.test');
        $test = $group->passage?->test;

        abort_unless($test !== null, 404);
        abort_unless($test->status === PublishStatus::Published, 404);
        abort_unless($group->question_type?->isDiagramBuilderType(), 404);

        return $this->diagram->streamDiagramImage($group);
    }
}
