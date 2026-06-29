<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningAudioAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListeningAudioStreamController extends Controller
{
    public function __construct(
        private readonly ListeningAudioAccessService $audioAccess,
        private readonly ListeningAttemptService $attempts,
    ) {}

    public function section(Request $request, ListeningAttempt $attempt, int $section): StreamedResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        return $this->audioAccess->streamSectionAudio($attempt, $section);
    }

    public function groupImage(Request $request, ListeningAttempt $attempt, ListeningQuestionGroup $group): StreamedResponse
    {
        if (config('listening.audio_access.use_signed_routes', true) && ! $request->hasValidSignature()) {
            abort(403);
        }

        $this->attempts->assertOwnedBy($attempt, $request->user());

        if ((int) $group->listening_test_id !== (int) $attempt->listening_test_id) {
            abort(404);
        }

        if (blank($group->image_path)) {
            abort(404);
        }

        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

        if (! $disk->exists($group->image_path)) {
            abort(404);
        }

        $absolutePath = $disk->path($group->image_path);
        $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return response()->stream(function () use ($absolutePath): void {
            $stream = fopen($absolutePath, 'rb');

            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Disposition' => 'inline',
        ]);
    }
}
