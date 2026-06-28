<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Actions\Listening\Audio\RetryListeningAudioProcessingAction;
use App\Actions\Listening\Audio\ValidateListeningAudioAction;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\RetryListeningAudioProcessingRequest;
use App\Models\Listening\ListeningAudio;
use App\Repositories\Listening\ListeningAudioRepository;
use App\Services\Listening\Audio\ListeningAudioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ListeningAudioProcessingController extends Controller
{
    public function __construct(
        private readonly ListeningAudioService $audios,
        private readonly RetryListeningAudioProcessingAction $retryProcessing,
        private readonly ValidateListeningAudioAction $validateAudio,
        private readonly ListeningAudioRepository $audioRepository,
    ) {}

    public function process(ListeningAudio $audio): RedirectResponse
    {
        $this->authorize('process', $audio);

        try {
            $this->audios->dispatchProcessing($audio);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Audio processing failed.');
        }

        return back()->with('status', 'Audio processing has been queued.');
    }

    public function retry(RetryListeningAudioProcessingRequest $request, ListeningAudio $audio): RedirectResponse
    {
        try {
            $this->retryProcessing->execute($audio, (bool) $request->boolean('force'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', 'Retry limit exceeded.');
        }

        return back()->with('status', 'Audio processing restarted successfully.');
    }

    public function generateWaveform(ListeningAudio $audio): RedirectResponse
    {
        $this->authorize('generateWaveform', $audio);
        $this->audios->dispatchWaveformGeneration($audio);

        return back()->with('status', 'Audio waveform generation has been queued.');
    }

    public function validateAudio(ListeningAudio $audio): RedirectResponse
    {
        $this->authorize('validateAudio', $audio);

        $result = $this->validateAudio->execute($audio);

        $this->audioRepository->update($audio, [
            'validation_status' => $result->status,
            'validation_errors' => $result->isValid() ? null : $result->errors(),
        ]);

        if (! $result->isValid()) {
            return back()->with('error', $result->errors()[0]['message'] ?? 'Invalid audio file.');
        }

        return back()->with('status', 'Audio validation completed successfully.');
    }
}
