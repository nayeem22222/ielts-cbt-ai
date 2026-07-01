<?php

declare(strict_types=1);

namespace App\Actions\Listening\Result;

use App\Repositories\Listening\Result\ListeningResultRepository;
use Illuminate\Support\Str;

class GenerateListeningResultCodeAction
{
    public function __construct(
        private readonly ListeningResultRepository $results,
    ) {}

    public function execute(): string
    {
        $prefix = (string) config('listening.results.code_prefix', 'LST');
        $year = (int) now()->year;
        $attempts = 0;

        do {
            $sequence = $this->results->nextSequenceForYear($year) + $attempts;
            $code = sprintf('%s-%d-%06d', $prefix, $year, $sequence);
            $attempts++;
        } while ($this->results->resultCodeExists($code) && $attempts < 20);

        if ($this->results->resultCodeExists($code)) {
            $code = sprintf('%s-%d-%06d-%s', $prefix, $year, $sequence, strtoupper(Str::random(4)));
        }

        return $code;
    }
}
