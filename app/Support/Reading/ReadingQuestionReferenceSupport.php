<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingQuestion;

final class ReadingQuestionReferenceSupport
{
    public const TYPE_OFFSET = 'offset';

    public const TYPE_PHRASE = 'phrase';

    public const TYPE_SENTENCE = 'sentence';

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_OFFSET,
            self::TYPE_PHRASE,
            self::TYPE_SENTENCE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'reference_type' => ['nullable', 'string', 'in:offset,phrase,sentence'],
            'reference_phrase' => ['nullable', 'string', 'max:10000'],
            'reference_sentence' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public static function resolveType(
        ?string $type,
        ?int $start,
        ?int $end,
        ?string $phrase,
        ?string $sentence,
    ): ?string {
        $type = trim((string) $type);

        if ($type === self::TYPE_PHRASE) {
            return self::TYPE_PHRASE;
        }

        if ($type === self::TYPE_SENTENCE) {
            return self::TYPE_SENTENCE;
        }

        if (trim((string) $phrase) !== '') {
            return self::TYPE_PHRASE;
        }

        if (trim((string) $sentence) !== '') {
            return self::TYPE_SENTENCE;
        }

        if ($type === self::TYPE_OFFSET) {
            return self::TYPE_OFFSET;
        }

        if ($start !== null && $end !== null && $end > $start) {
            return self::TYPE_OFFSET;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function applyAttributes(ReadingQuestion $question, array $data): void
    {
        if (array_key_exists('reference_paragraph', $data)) {
            $question->reference_paragraph = self::nullableString($data['reference_paragraph']);
            $question->paragraph_reference = $question->reference_paragraph;
        } elseif (array_key_exists('paragraph_reference', $data)) {
            $question->paragraph_reference = self::nullableString($data['paragraph_reference']);
            $question->reference_paragraph = $question->paragraph_reference;
        }

        if (array_key_exists('reference_start_offset', $data)) {
            $question->reference_start_offset = self::nullableInt($data['reference_start_offset']);
        }

        if (array_key_exists('reference_end_offset', $data)) {
            $question->reference_end_offset = self::nullableInt($data['reference_end_offset']);
        }

        if (array_key_exists('reference_phrase', $data)) {
            $question->reference_phrase = self::normalizeReferenceText($data['reference_phrase']);
        }

        if (array_key_exists('reference_sentence', $data)) {
            $question->reference_sentence = self::normalizeReferenceText($data['reference_sentence']);
        }

        $question->reference_type = self::resolveType(
            array_key_exists('reference_type', $data) ? (string) ($data['reference_type'] ?? '') : $question->reference_type,
            $question->reference_start_offset,
            $question->reference_end_offset,
            $question->reference_phrase,
            $question->reference_sentence,
        );

        if (in_array($question->reference_type, [self::TYPE_PHRASE, self::TYPE_SENTENCE], true)) {
            $question->reference_start_offset = null;
            $question->reference_end_offset = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function reviewAttributes(ReadingQuestion $question): array
    {
        $paragraph = $question->reference_paragraph ?? $question->paragraph_reference;
        $type = self::resolveType(
            (string) ($question->reference_type ?? ''),
            $question->reference_start_offset,
            $question->reference_end_offset,
            $question->reference_phrase,
            $question->reference_sentence,
        );

        $usesPhraseOrSentence = in_array($type, [self::TYPE_PHRASE, self::TYPE_SENTENCE], true)
            || trim((string) ($question->reference_phrase ?? '')) !== ''
            || trim((string) ($question->reference_sentence ?? '')) !== '';

        return [
            'reference_type' => $type,
            'reference_phrase' => $question->reference_phrase,
            'reference_sentence' => $question->reference_sentence,
            'reference_start_offset' => $usesPhraseOrSentence ? null : $question->reference_start_offset,
            'reference_end_offset' => $usesPhraseOrSentence ? null : $question->reference_end_offset,
            'reference_paragraph' => $paragraph,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function normalizeReferenceText(mixed $value): ?string
    {
        $value = self::nullableString($value);

        if ($value === null) {
            return null;
        }

        $value = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2032}", "\u{2033}", '"', "'", '`'],
            '',
            $value,
        );

        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value) ?? $value;
        $value = preg_replace('/[\x{2010}-\x{2014}\x{2212}]/u', '-', $value) ?? $value;

        return self::nullableString($value);
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
