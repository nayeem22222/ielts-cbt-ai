<?php

declare(strict_types=1);

use App\Services\Listening\Audio\FakeListeningFfmpegRunner;
use App\Services\Listening\Audio\ListeningWaveformService;

it('generates waveform peaks between zero and one', function (): void {
    app()->instance(\App\Services\Listening\Audio\ListeningFfmpegRunnerInterface::class, new FakeListeningFfmpegRunner);
    $service = app(ListeningWaveformService::class);
    $peaks = $service->generatePeaks('sample.mp3', 100);

    expect($peaks)->toHaveCount(100);

    foreach ($peaks as $peak) {
        expect($peak)->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(1.0);
    }
});
