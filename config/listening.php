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

    'audio' => [
        'disk' => env('LISTENING_AUDIO_DISK', 'public'),

        'directories' => [
            'original' => 'listening/audio/original',
            'processed' => 'listening/audio/processed',
            'normalized' => 'listening/audio/normalized',
            'waveforms' => 'listening/audio/waveforms',
            'previews' => 'listening/audio/previews',
        ],

        'allowed_mimes' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/x-wav',
            'audio/mp4',
            'audio/aac',
            'audio/ogg',
            'audio/x-m4a',
        ],

        'allowed_extensions' => ['mp3', 'wav', 'm4a', 'aac', 'ogg'],

        'max_file_size_mb' => 100,

        'min_duration_seconds' => 30,
        'max_duration_seconds' => 3600,

        'target_format' => 'mp3',
        'target_bitrate' => '128k',
        'target_sample_rate' => 44100,
        'target_channels' => 2,

        'normalize_audio' => true,
        'target_loudness_lufs' => -16,

        'generate_waveform' => true,
        'waveform_samples' => 1000,

        'queue' => env('LISTENING_AUDIO_QUEUE', 'default'),

        'retry_limit' => 3,

        'ffmpeg' => [
            'enabled' => true,
            'binary' => env('FFMPEG_BINARY', 'ffmpeg'),
            'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
            'timeout' => 300,
        ],
    ],

    'publishing' => [
        'require_valid_audio' => true,
        'require_processed_audio' => true,
        'require_waveform' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Pipeline (Volume 5A)
    |--------------------------------------------------------------------------
    |
    | Production-grade queue pipeline settings.
    |
    | Recommended .env:
    |   QUEUE_CONNECTION=redis
    |   LISTENING_AUDIO_QUEUE_CONNECTION=database
    |   LISTENING_AUDIO_QUEUE=listening-audio
    |   FFMPEG_BINARY=ffmpeg
    |   FFPROBE_BINARY=ffprobe
    |
    | Recommended worker:
    |   php artisan queue:work redis --queue=listening-audio,default --timeout=900 --tries=3
    |
    | Database queue fallback:
    |   php artisan queue:work database --queue=listening-audio,default --timeout=900 --tries=3
    |
    */
    'audio_pipeline' => [
        'version' => '1.0.0',

        'queue' => env('LISTENING_AUDIO_QUEUE', 'listening-audio'),

        'connection' => env('LISTENING_AUDIO_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

        'lock_ttl_seconds' => 900,

        'job_timeout_seconds' => 900,

        'job_tries' => 3,

        'backoff_seconds' => [60, 300, 900],

        'retry_stuck_after_minutes' => 30,

        'max_retry_count' => 3,

        'ffmpeg' => [
            'binary' => env('FFMPEG_BINARY', 'ffmpeg'),
            'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
            'timeout' => 600,
            'threads' => 2,
            'hide_banner' => true,
        ],

        'conversion' => [
            'target_format' => 'mp3',
            'codec' => 'libmp3lame',
            'bitrate' => '128k',
            'sample_rate' => 44100,
            'channels' => 2,
        ],

        'normalization' => [
            'enabled' => true,
            'filter' => 'loudnorm',
            'target_lufs' => -16,
            'true_peak' => -1.5,
            'lra' => 11,
            'strict' => false,
        ],

        'silence_detection' => [
            'enabled' => true,
            'noise_threshold_db' => -35,
            'min_silence_duration' => 2,
            'warn_if_total_silence_percent_above' => 20,
        ],

        'waveform' => [
            'enabled' => true,
            'samples' => 1000,
            'fallback_on_failure' => true,
            'preview_image' => true,
        ],

        'storage' => [
            'keep_original' => true,
            'overwrite_processed' => false,
            'version_processed_files' => true,
        ],
    ],

    'student_access' => [
        'minimum_active_sections' => 1,
        'minimum_active_questions' => 1,
        'block_start_without_audio' => false,
        'show_debug_unavailability' => env('APP_ENV') === 'local',
    ],

    'student_player' => [
        'official_mode' => true,
        'show_section_tabs' => true,
        'show_question_palette' => false,
        'auto_save_interval_seconds' => 10,
        'auto_save_debounce_ms' => 700,
        'allow_audio_replay' => false,
        'allow_audio_seek' => false,
        'allow_playback_speed_change' => false,
        'allow_transcript_live' => false,
        'confirm_submit' => true,
        'mobile_palette_collapsible' => true,
    ],

    'audio_access' => [
        'use_signed_routes' => true,
        'signed_url_ttl_minutes' => 60,
        'stream_audio_through_app' => true,
        'prevent_download_headers' => true,
    ],

    'student_attempts' => [
        'allow_multiple_in_progress_attempts' => false,
        'resume_existing_attempt' => true,
        'create_answer_rows_on_start' => true,
    ],

    'navigation' => [
        'save_position_on_jump' => true,
        'validate_section_question_match' => true,
        'allow_jump_to_any_question' => true,
        'allow_section_switch' => true,
    ],

    'autosave' => [
        'enabled' => true,
        'debounce_ms' => 700,
        'bulk_interval_seconds' => 10,
        'retry_attempts' => 3,
        'retry_delay_ms' => 1500,
        'use_local_storage_backup' => true,
        'prevent_submit_when_unsynced' => true,
        'answer_hashing' => true,
    ],

    'recovery' => [
        'enabled' => true,
        'show_recovery_modal' => true,
        'prefer_newer_answer' => true,
    ],

    'official_flow' => [
        'enabled' => true,
        'default_listening_minutes' => 30,
        'default_transfer_minutes' => 10,
        'allow_transfer_time' => true,
        'allow_answer_edit_during_transfer' => true,
        'auto_enter_transfer_phase' => true,
        'auto_submit_on_expiry' => true,
        'server_timer_sync_interval_seconds' => 20,
        'warning_thresholds_seconds' => [600, 300, 60],
    ],

    'official_audio' => [
        'play_once' => true,
        'disable_seek' => true,
        'disable_replay' => true,
        'disable_speed_change' => true,
        'allow_audio_only_in_listening_phase' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Answer Normalization (Volume 9A)
    |--------------------------------------------------------------------------
    */
    'normalization' => [
        'case_sensitive_default' => false,
        'ignore_punctuation_default' => true,
        'ignore_articles_default' => true,
        'allow_plural_default' => true,
        'normalize_hyphen_default' => true,
        'normalize_unicode' => true,

        'articles' => ['a', 'an', 'the'],

        'british_american_spelling' => [
            'enabled' => false,
            'map' => [
                'colour' => 'color',
                'centre' => 'center',
                'theatre' => 'theater',
                'licence' => 'license',
                'programme' => 'program',
            ],
        ],

        'numbers' => [
            'words_to_numbers' => true,
            'ordinals' => true,
        ],

        'dates' => [
            'enabled' => true,
            'allow_ordinal_suffix' => true,
            'ambiguous_numeric_dates' => false,
        ],

        'times' => [
            'enabled' => true,
            'normalize_to_24_hour' => true,
        ],

        'currency' => [
            'enabled' => true,
            'normalize_symbols' => true,
        ],

        'regex_answers' => [
            'enabled' => true,
            'max_pattern_length' => 255,
        ],

        'word_limit' => [
            'enforce' => true,
            'hyphenated_as_one' => true,
            'numbers_count_as_one' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Answer Engine (Volume 9)
    |--------------------------------------------------------------------------
    */
    'answer_engine' => [
        'version' => env('LISTENING_ANSWER_ENGINE_VERSION', '1.0.0'),

        'mode' => env('LISTENING_EVALUATION_MODE', 'queue'),

        'queue' => env('LISTENING_EVALUATION_QUEUE', 'default'),

        'evaluate_on_submit' => env('LISTENING_EVALUATE_ON_SUBMIT', true),

        'allow_recheck' => env('LISTENING_ALLOW_EVALUATION_RECHECK', true),

        'preserve_snapshots' => true,

        'lock_ttl_seconds' => 120,

        'normalization' => [
            'trim' => true,
            'collapse_whitespace' => true,
            'lowercase_when_not_case_sensitive' => true,
            'remove_punctuation_when_allowed' => true,
            'normalize_hyphen_when_allowed' => true,
            'remove_articles_when_allowed' => true,
            'plural_variants_when_allowed' => true,
        ],

        'articles' => ['a', 'an', 'the'],

        'punctuation_pattern' => '/[[:punct:]]+/u',

        'date_formats' => ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'],

        'time_formats' => ['H:i', 'g:i A', 'g:i a', 'H:i:s'],

        'multiple_answer' => [
            'partial_marking' => env('LISTENING_MULTIPLE_ANSWER_PARTIAL', false),
            'order_sensitive' => false,
        ],

        'word_limit' => [
            'count_numbers_as_words' => false,
            'count_hyphenated_as_one' => true,
        ],

        'band_score_map' => [
            ['min' => 39, 'max' => 40, 'band' => 9.0],
            ['min' => 37, 'max' => 38, 'band' => 8.5],
            ['min' => 35, 'max' => 36, 'band' => 8.0],
            ['min' => 32, 'max' => 34, 'band' => 7.5],
            ['min' => 30, 'max' => 31, 'band' => 7.0],
            ['min' => 26, 'max' => 29, 'band' => 6.5],
            ['min' => 23, 'max' => 25, 'band' => 6.0],
            ['min' => 18, 'max' => 22, 'band' => 5.5],
            ['min' => 16, 'max' => 17, 'band' => 5.0],
            ['min' => 13, 'max' => 15, 'band' => 4.5],
            ['min' => 11, 'max' => 12, 'band' => 4.0],
            ['min' => 6, 'max' => 9, 'band' => 3.5],
            ['min' => 4, 'max' => 5, 'band' => 3.0],
            ['min' => 2, 'max' => 3, 'band' => 2.5],
            ['min' => 0, 'max' => 1, 'band' => 2.0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Results (Volume 10)
    |--------------------------------------------------------------------------
    */
    'results' => [
        'code_prefix' => env('LISTENING_RESULT_CODE_PREFIX', 'LST'),
        'show_correct_answers_default' => env('LISTENING_SHOW_CORRECT_ANSWERS_DEFAULT', true),
        'show_accepted_answers_to_students' => env('LISTENING_SHOW_ACCEPTED_ANSWERS_TO_STUDENTS', false),
        'auto_build_after_evaluation' => env('LISTENING_AUTO_BUILD_RESULT_AFTER_EVALUATION', true),
        'visible_to_student_default' => env('LISTENING_RESULT_VISIBLE_TO_STUDENT_DEFAULT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Review (Volume 10A)
    |--------------------------------------------------------------------------
    */
    'review' => [
        'enabled' => env('LISTENING_REVIEW_ENABLED', true),
        'build_after_result' => env('LISTENING_REVIEW_BUILD_AFTER_RESULT', true),
        'show_correct_answer_default' => env('LISTENING_REVIEW_SHOW_CORRECT_ANSWER_DEFAULT', true),
        'show_accepted_answers_to_students' => env('LISTENING_REVIEW_SHOW_ACCEPTED_ANSWERS', false),
        'show_transcript_highlight_default' => env('LISTENING_REVIEW_SHOW_TRANSCRIPT_HIGHLIGHT', false),
        'show_audio_review_default' => env('LISTENING_REVIEW_SHOW_AUDIO_REVIEW', false),
        'show_explanation_default' => env('LISTENING_REVIEW_SHOW_EXPLANATION', true),
        'allow_student_copy_transcript' => env('LISTENING_REVIEW_ALLOW_COPY_TRANSCRIPT', false),
        'audio_review_signed_url_ttl_minutes' => env('LISTENING_REVIEW_AUDIO_SIGNED_URL_TTL', 30),
    ],
];
