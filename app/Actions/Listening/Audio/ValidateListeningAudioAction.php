<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\DTOs\Listening\Audio\ListeningAudioValidationResultData;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningAudioValidationService;

class ValidateListeningAudioAction
{
    public function __construct(
        private readonly ListeningAudioValidationService $validation,
    ) {}

    public function execute(ListeningAudio $audio): ListeningAudioValidationResultData
    {
        $errors = $this->validation->validateForPublish($audio);

        return new ListeningAudioValidationResultData(
            $errors === [] ? \App\Enums\Listening\ListeningAudioValidationStatus::Valid : \App\Enums\Listening\ListeningAudioValidationStatus::Invalid,
            $errors,
        );
    }
}
