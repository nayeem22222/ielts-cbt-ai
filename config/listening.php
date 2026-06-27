<?php

declare(strict_types=1);

return [
    'transcript' => [
        'require_for_publish' => env('LISTENING_REQUIRE_TRANSCRIPT_FOR_PUBLISH', false),
        'strict_audio_match' => env('LISTENING_STRICT_TRANSCRIPT_AUDIO_MATCH', true),
        'allow_review_visibility' => env('LISTENING_ALLOW_REVIEW_TRANSCRIPT', true),
        'max_text_length' => 100000,
        'allow_ai_generated' => env('LISTENING_ALLOW_AI_GENERATED_TRANSCRIPTS', false),
    ],

    'passage' => [
        'admin_only' => true,
        'allow_question_builder_reference' => true,
    ],
];
