<?php

declare(strict_types=1);

use App\Services\Listening\Audio\FakeListeningFfmpegRunner;
use App\Services\Listening\Audio\ListeningAudioValidationService;
use App\Services\Listening\Audio\ListeningWaveformService;
use Illuminate\Http\UploadedFile;

it('validates allowed audio mime types', function (): void {
    $service = app(ListeningAudioValidationService::class);
    $result = $service->validateFile(UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg'));

    expect($result->isValid())->toBeTrue();
});

it('rejects unsupported audio mime types', function (): void {
    $service = app(ListeningAudioValidationService::class);
    $result = $service->validateFile(UploadedFile::fake()->create('test.txt', 100, 'text/plain'));

    expect($result->isValid())->toBeFalse()
        ->and($result->errors()[0]['code'])->toBe('invalid_mime');
});

it('generates normalized peaks through waveform service', function (): void {
    app()->instance(\App\Services\Listening\Audio\ListeningFfmpegRunnerInterface::class, new FakeListeningFfmpegRunner);
    $service = app(ListeningWaveformService::class);
    $peaks = $service->generatePeaks('fake-path.mp3', 20);

    expect($peaks)->toHaveCount(20)
        ->and(max($peaks))->toBeLessThanOrEqual(1.0)
        ->and(min($peaks))->toBeGreaterThanOrEqual(0.0);
});
