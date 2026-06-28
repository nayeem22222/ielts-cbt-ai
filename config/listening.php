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

    'questions' => [
        'allow_draft_without_answer' => env('LISTENING_ALLOW_DRAFT_WITHOUT_ANSWER', true),
        'default_marks' => 1,
        'max_questions_per_test' => 40,
        'questions_per_section' => 10,
    ],

    'question_types' => [
        'enabled' => [
            'mcq',
            'multiple_answer',
            'matching',
            'map_labelling',
            'plan_labelling',
            'diagram_labelling',
            'form_completion',
            'note_completion',
            'table_completion',
            'flowchart_completion',
            'sentence_completion',
            'summary_completion',
            'short_answer',
        ],

        'completion_blank_pattern' => '/\[blank:(\d+)\]/',

        'labelling' => [
            'coordinate_unit' => 'percent',
            'min_coordinate' => 0,
            'max_coordinate' => 100,
            'require_image' => true,
        ],

        'multiple_answer' => [
            'allow_partial_marking' => false,
        ],

        'matching' => [
            'allow_choice_reuse_default' => false,
        ],
    ],
];
