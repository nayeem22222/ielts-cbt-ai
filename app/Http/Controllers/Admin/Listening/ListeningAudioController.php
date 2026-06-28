<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Actions\Listening\Audio\UploadListeningAudioAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\StoreListeningAudioRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningAudioRequest;
use App\Models\Listening\ListeningAudio;
use App\Models\User;
use App\Services\Listening\Audio\ListeningAudioService;
use App\Services\Listening\Audio\ListeningWaveformService;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListeningAudioController extends Controller
{
    public function __construct(
        private readonly ListeningAudioService $audios,
        private readonly UploadListeningAudioAction $uploadAudio,
        private readonly ListeningWaveformService $waveforms,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ListeningAudio::class);
        $filters = $this->filtersFromRequest($request);

        return view('admin.listening.audios.index', $this->sharedViewData([
            'records' => $this->audios->paginateForAdmin($filters),
            'filters' => $filters,
            'uploaders' => User::query()->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function create(): View
    {
        $this->authorize('create', ListeningAudio::class);

        return view('admin.listening.audios.create', $this->sharedViewData([
            'audio' => new ListeningAudio(),
        ]));
    }

    public function store(StoreListeningAudioRequest $request): RedirectResponse
    {
        try {
            $audio = $this->uploadAudio->execute(
                $request->file('audio_file'),
                $request->validated(),
                $request->user()?->id,
            );
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors())->with('error', 'Audio upload failed.');
        }

        return redirect()
            ->route('admin.listening.audios.show', $audio)
            ->with('status', 'Listening audio uploaded successfully. Audio processing has been queued.');
    }

    public function show(ListeningAudio $audio): View
    {
        $this->authorize('view', $audio);

        return view('admin.listening.audios.show', $this->sharedViewData([
            'audio' => $audio->load('uploadedBy'),
            'readiness' => $this->audios->getAudioReadiness($audio),
            'usage' => $this->audios->getSectionUsage($audio),
            'waveform' => $this->waveforms->loadWaveform($audio),
            'pipelineQueue' => [
                'pending_jobs' => ListeningAudioPipelineDispatcher::pendingJobCount(),
                'has_job_for_audio' => ListeningAudioPipelineDispatcher::hasQueuedJobForAudio($audio->id),
                'worker_command' => ListeningAudioPipelineDispatcher::workerCommand(),
            ],
        ]));
    }

    public function edit(ListeningAudio $audio): View
    {
        $this->authorize('update', $audio);

        return view('admin.listening.audios.edit', $this->sharedViewData([
            'audio' => $audio,
        ]));
    }

    public function update(UpdateListeningAudioRequest $request, ListeningAudio $audio): RedirectResponse
    {
        try {
            $this->audios->update($audio, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.audios.show', $audio)
            ->with('status', 'Listening audio updated successfully.');
    }

    public function destroy(ListeningAudio $audio): RedirectResponse
    {
        $this->authorize('delete', $audio);

        try {
            $this->audios->delete($audio);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.audios.index')
            ->with('status', 'Listening audio deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'processing_status' => $request->string('processing_status')->toString(),
            'validation_status' => $request->string('validation_status')->toString(),
            'format' => $request->string('format')->toString(),
            'uploaded_by' => $request->integer('uploaded_by') ?: null,
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(array $data = []): array
    {
        return array_merge([
            'routePrefix' => 'admin.listening.audios',
        ], $data);
    }
}
