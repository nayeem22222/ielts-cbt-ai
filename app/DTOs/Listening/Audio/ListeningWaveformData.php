<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio;

final readonly class ListeningWaveformData
{
    /**
     * @param  list<float>  $peaks
     */
    public function __construct(
        public int $version,
        public int $samples,
        public float $durationSeconds,
        public array $peaks,
        public bool $normalized,
        public string $generatedAt,
        public ?string $jsonPath = null,
        public ?string $previewPath = null,
        public string $quality = 'full',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toJsonDocument(): array
    {
        return [
            'version' => $this->version,
            'samples' => $this->samples,
            'duration_seconds' => round($this->durationSeconds, 2),
            'peaks' => array_values($this->peaks),
            'normalized' => $this->normalized,
            'generated_at' => $this->generatedAt,
            'quality' => $this->quality,
        ];
    }
}
