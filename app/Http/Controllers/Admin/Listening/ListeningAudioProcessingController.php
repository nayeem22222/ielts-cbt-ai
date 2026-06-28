<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Actions\Listening\Audio\Pipeline\StartListeningAudioPipelineAction;
use App\Actions\Listening\Audio\RetryListeningAudioProcessingAction;
use App\Actions\Listening\Audio\ValidateListeningAudioAction;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\RetryListeningAudioProcessingRequest;
use App\Models\Listening\ListeningAudio;
use App\Repositories\Listening\ListeningAudioRepository;
use App\Services\Listening\Audio\ListeningAudioService;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListeningAudioProcessingController extends Controller
{
    public function __construct(
        private readonly ListeningAudioService $audios,
        private readonly RetryListeningAudioProcessingAction $retryProcessing,
        private readonly ValidateListeningAudioAction $validateAudio,
        private readonly ListeningAudioRepository $audioRepository,
        private readonly StartListeningAudioPipelineAction $startPipeline,
    ) {}

    public function process(ListeningAudio $audio): RedirectResponse
    {
        $this->authorize('process', $audio);

        try {
            $dispatched = $this->startPipeline->execute($audio, force: false);

            if (! $dispatched) {
                return back()->with('error', 'Audio is already being processed. Use Force Retry to bypass.');
            }
        } catch (\Throwable $exception) {
            return back()->with('error', 'Audio processing could not be queued: '.$exception->getMessage());
        }

        return back()->with('status', 'Audio pipeline job has been queued. '.ListeningAudioPipelineDispatcher::queuedStatusMessage());
    }

    public function retry(Request $request, ListeningAudio $audio): RedirectResponse
    {
        $this->authorize('retry', $audio);
        $force = (bool) $request->boolean('force');
        $maxRetries = (int) config('listening.audio_pipeline.max_retry_count', 3);

        // Enforce retry limit unless forced
        if (! $force && (int) $audio->retry_count >= $maxRetries) {
            return back()->withErrors([
                'retry' => "Retry limit of {$maxRetries} reached. Use Force Retry to override.",
            ])->with('error', 'Retry limit exceeded.');
        }

        try {
            $dispatched = $this->startPipeline->execute($audio, force: $force);

            if (! $dispatched) {
                return back()->with('error', 'Audio is already being processed. Use Force Retry to bypass.');
            }
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', 'Retry failed.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Retry failed: '.$e->getMessage());
        }

        return back()->with('status', ($force ? 'Force retry dispatched.' : 'Audio processing restarted. ').ListeningAudioPipelineDispatcher::queuedStatusMessage());
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
