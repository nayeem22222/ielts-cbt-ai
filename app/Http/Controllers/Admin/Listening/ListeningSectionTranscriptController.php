<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\AttachListeningTranscriptRequest;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTranscript;
use App\Services\Listening\ListeningTranscriptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ListeningSectionTranscriptController extends Controller
{
    public function __construct(
        private readonly ListeningTranscriptService $transcripts,
    ) {}

    public function attach(
        AttachListeningTranscriptRequest $request,
        ListeningTest $listeningTest,
        ListeningSection $section,
    ): RedirectResponse {
        $transcript = ListeningTranscript::query()->findOrFail($request->integer('transcript_id'));

        $this->authorize('attachToSection', $transcript);

        try {
            $this->transcripts->attachToSection(
                $listeningTest,
                $section,
                $transcript,
                $request->boolean('force_attach'),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', $exception->errors()['transcript_id'][0] ?? 'Transcript could not be attached.');
        }

        return back()->with('status', 'Transcript attached to section successfully.');
    }

    public function detach(ListeningTest $listeningTest, ListeningSection $section): RedirectResponse
    {
        $this->authorize('detachFromSection', ListeningTranscript::class);

        try {
            $this->transcripts->detachFromSection($listeningTest, $section);
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->with('error', $exception->errors()['section'][0] ?? 'Transcript could not be detached.');
        }

        return back()->with('status', 'Transcript detached from section successfully.');
    }
}
