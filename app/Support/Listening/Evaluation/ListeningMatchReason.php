<?php

declare(strict_types=1);

namespace App\Support\Listening\Evaluation;

final class ListeningMatchReason
{
    public const EXACT_MATCH = 'exact_match';

    public const ACCEPTED_ANSWER_MATCH = 'accepted_answer_match';

    public const NORMALIZED_MATCH = 'normalized_match';

    public const INCORRECT_ANSWER = 'incorrect_answer';

    public const UNANSWERED = 'unanswered';

    public const WORD_LIMIT_EXCEEDED = 'word_limit_exceeded';

    public const OPTION_NOT_FOUND = 'option_not_found';

    public const PARTIAL_MATCH = 'partial_match';

    public const MANUAL_REVIEW_REQUIRED = 'manual_review_required';

    public const INVALID_ANSWER_FORMAT = 'invalid_answer_format';
}
