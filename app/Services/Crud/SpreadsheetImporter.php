<?php

declare(strict_types=1);

namespace App\Services\Crud;

use App\Crud\ImportResult;
use App\Services\Service;
use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class SpreadsheetImporter extends Service
{
    /**
     * @param  callable(array<string, string>): bool  $rowHandler
     */
    public function import(UploadedFile $file, callable $rowHandler): ImportResult
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $reader = match ($extension) {
            'csv' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => null,
        };

        if ($reader === null) {
            return new ImportResult(errors: ['Unsupported file type. Upload CSV or XLSX.']);
        }

        $reader->open($file->getRealPath());

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $headers = null;

            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
                $cells = array_map(
                    fn ($cell) => trim((string) $cell->getValue()),
                    $row->getCells()
                );

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($cells);

                    continue;
                }

                if ($this->isEmptyRow($cells)) {
                    continue;
                }

                $payload = $this->combineRow($headers, $cells);

                try {
                    $handled = $rowHandler($payload);

                    if ($handled) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $exception) {
                    $errors[] = "Row {$rowNumber}: ".$exception->getMessage();
                }
            }
        }

        $reader->close();

        return new ImportResult($imported, $skipped, $errors);
    }

    /**
     * @param  list<string>  $cells
     * @return list<string>
     */
    private function normalizeHeaders(array $cells): array
    {
        return array_map(
            fn (string $header): string => strtolower(str_replace(' ', '_', $header)),
            $cells
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $cells
     * @return array<string, string>
     */
    private function combineRow(array $headers, array $cells): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = $cells[$index] ?? '';
        }

        return $row;
    }

    /**
     * @param  list<string>  $cells
     */
    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== '') {
                return false;
            }
        }

        return true;
    }
}
