<?php

declare(strict_types=1);

namespace App\Services\Crud;

use App\Services\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter extends Service
{
    /**
     * @param  array<string, string>  $columns
     */
    public function stream(string $filename, Builder $query, array $columns): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, array_values($columns));

            $query->chunk(200, function ($records) use ($handle, $columns): void {
                foreach ($records as $record) {
                    fputcsv($handle, $this->rowValues($record, $columns));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<string, string>  $columns
     * @return list<scalar|null>
     */
    private function rowValues(Model $record, array $columns): array
    {
        $values = [];

        foreach (array_keys($columns) as $attribute) {
            $values[] = data_get($record, $attribute);
        }

        return $values;
    }
}
