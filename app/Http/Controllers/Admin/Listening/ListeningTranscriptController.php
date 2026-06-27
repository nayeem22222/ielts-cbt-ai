<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Listening\ListeningTranscriptSourceType;
use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\StoreListeningTranscriptRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningTranscriptRequest;
use App\Http\Requests\Admin\Listening\UpdateTimestampedTranscriptRequest;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningTranscript;
use App\Repositories\Listening\ListeningTranscriptRepository;
use App\Services\Listening\ListeningPassageService;
use App\Services\Listening\ListeningTranscriptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListeningTranscriptController extends Controller
{
    public function __construct(
        private readonly ListeningTranscriptService $transcripts,
        private readonly ListeningPassageService $passages,
        private readonly ListeningTranscriptRepository $transcriptRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ListeningTranscript::class);

        $filters = $this->filtersFromRequest($request);

        return view('admin.listening.transcripts.index', $this->sharedViewData([
            'records' => $this->transcripts->paginateForAdmin($filters),
            'filters' => $filters,
            'audios' => ListeningAudio::query()->orderBy('original_name')->get(['id', 'original_name']),
            'visibilities' => ListeningTranscriptVisibility::cases(),
            'sourceTypes' => ListeningTranscriptSourceType::cases(),
        ]));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ListeningTranscript::class);

        $audioId = $request->integer('listening_audio_id') ?: null;
        $title = $request->string('title')->toString() ?: null;

        return view('admin.listening.transcripts.create', $this->sharedViewData([
            'transcript' => new ListeningTranscript([
                'listening_audio_id' => $audioId,
                'title' => $title,
                'language' => 'en',
                'visibility' => ListeningTranscriptVisibility::AdminOnly,
                'source_type' => ListeningTranscriptSourceType::Manual,
                'is_official' => false,
            ]),
            'audios' => ListeningAudio::query()->orderBy('original_name')->get(['id', 'original_name']),
            'visibilities' => ListeningTranscriptVisibility::cases(),
            'sourceTypes' => ListeningTranscriptSourceType::cases(),
            'returnUrl' => $this->safeReturnUrl($request->string('return')->toString() ?: null),
            'sectionContext' => $title,
        ]));
    }

    public function store(StoreListeningTranscriptRequest $request): RedirectResponse
    {
        try {
            $transcript = $this->transcripts->create(array_merge(
                $request->validated(),
                ['created_by' => $request->user()?->id],
            ));
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', 'Transcript could not be saved.');
        }

        $returnUrl = $this->safeReturnUrl($request->input('return_url'));

        if ($returnUrl !== null) {
            return redirect($returnUrl)
                ->with('status', 'Listening transcript created successfully. You can attach it to the section below.');
        }

        return redirect()
            ->route('admin.listening.transcripts.show', $transcript)
            ->with('status', 'Listening transcript created successfully.');
    }

    public function show(ListeningTranscript $transcript): View
    {
        $this->authorize('view', $transcript);

        $transcript = $this->transcriptRepository->findWithRelations($transcript->id)
            ?? abort(404);

        return view('admin.listening.transcripts.show', $this->sharedViewData([
            'transcript' => $transcript,
            'readiness' => $this->transcripts->getTranscriptReadiness($transcript),
            'passagePreview' => $this->passages->buildAdminPassagePreview($transcript),
            'futureReview' => $this->passages->prepareForFutureReview($transcript),
        ]));
    }

    public function edit(ListeningTranscript $transcript): View
    {
        $this->authorize('update', $transcript);

        $transcript->load(['audio']);

        return view('admin.listening.transcripts.edit', $this->sharedViewData([
            'transcript' => $transcript,
            'audios' => ListeningAudio::query()->orderBy('original_name')->get(['id', 'original_name']),
            'visibilities' => ListeningTranscriptVisibility::cases(),
            'sourceTypes' => ListeningTranscriptSourceType::cases(),
        ]));
    }

    public function update(UpdateListeningTranscriptRequest $request, ListeningTranscript $transcript): RedirectResponse
    {
        try {
            $this->transcripts->update($transcript, $request->validated());
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', 'Transcript could not be saved.');
        }

        return redirect()
            ->route('admin.listening.transcripts.show', $transcript)
            ->with('status', 'Listening transcript updated successfully.');
    }

    public function destroy(ListeningTranscript $transcript): RedirectResponse
    {
        $this->authorize('delete', $transcript);

        if (! $this->transcripts->delete($transcript)) {
            return back()->with('error', 'Transcript could not be saved.');
        }

        return redirect()
            ->route('admin.listening.transcripts.index')
            ->with('status', 'Listening transcript deleted successfully.');
    }

    public function updateTimestamps(UpdateTimestampedTranscriptRequest $request, ListeningTranscript $transcript): RedirectResponse
    {
        try {
            $this->transcripts->updateTimestampedTranscript(
                $transcript,
                $request->validated('timestamped_transcript'),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', 'Invalid timestamped transcript.');
        }

        return redirect()
            ->route('admin.listening.transcripts.show', $transcript)
            ->with('status', 'Timestamped transcript updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'audio_id' => $request->input('audio_id'),
            'visibility' => $request->input('visibility'),
            'is_official' => $request->input('is_official'),
            'language' => $request->input('language'),
            'source_type' => $request->input('source_type'),
            'created_by' => $request->input('created_by'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'sort_by' => $request->input('sort_by'),
            'sort_direction' => $request->input('sort_direction'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(array $data = []): array
    {
        return array_merge([
            'routePrefix' => 'admin.listening.transcripts',
        ], $data);
    }

    private function safeReturnUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && str_starts_with($path, '/admin')) {
            return $url;
        }

        return null;
    }
}
