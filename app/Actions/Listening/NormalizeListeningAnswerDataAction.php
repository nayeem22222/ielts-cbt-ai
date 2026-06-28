<?php

declare(strict_types=1);

namespace App\Actions\Listening;

class NormalizeListeningAnswerDataAction
{
    /**
     * @return list<array<string, mixed>>
     */
    public function execute(array|string|null $data, string $defaultType = 'text'): array
    {
        if ($data === null || $data === '' || $data === []) {
            return [];
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeArray($decoded, $defaultType);
            }

            return [['value' => trim($data), 'type' => $defaultType]];
        }

        return $this->normalizeArray($data, $defaultType);
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function normalizeArray(array $data, string $defaultType): array
    {
        if (array_is_list($data) && isset($data[0]) && is_array($data[0])) {
            return array_values(array_map(fn (array $item): array => $this->normalizeItem($item, $defaultType), $data));
        }

        if (array_is_list($data) && ! isset($data[0])) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_map(fn (mixed $value): array => [
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
                'type' => $defaultType,
            ], $data));
        }

        return [$this->normalizeItem($data, $defaultType)];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item, string $defaultType): array
    {
        if (isset($item['label'], $item['value'])) {
            return [
                'label' => (string) $item['label'],
                'value' => (string) $item['value'],
                'type' => (string) ($item['type'] ?? 'map_label'),
            ];
        }

        return [
            'value' => (string) ($item['value'] ?? ''),
            'type' => (string) ($item['type'] ?? $defaultType),
        ];
    }
}
