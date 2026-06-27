<?php

declare(strict_types=1);

namespace App\Actions\Listening;

class NormalizeTranscriptTextAction
{
    public function execute(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        $lines = array_map(static fn (string $line): string => rtrim($line), explode("\n", trim($text)));

        return implode("\n", $lines);
    }
}
