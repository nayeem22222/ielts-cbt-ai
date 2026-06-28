<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListeningAudioUploadService
{
    public function __construct(
        private readonly ListeningAudioStorageService $storage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function storeOriginal(UploadedFile $file): array
    {
        $disk = $this->storage->disk();
        $directory = $this->storage->directory('original');
        $storedName = $this->generateStoredName($file);
        $path = $file->storeAs($directory, $storedName, $disk);
        $absolutePath = Storage::disk($disk)->path($path);

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'mime_type' => $file->getMimeType(),
            'extension' => strtolower((string) $file->getClientOriginalExtension()),
            'file_size' => (int) $file->getSize(),
            'checksum' => $this->calculateChecksum($absolutePath),
        ];
    }

    public function generateStoredName(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
    }

    public function calculateChecksum(string $path): string
    {
        return hash_file('sha256', $path) ?: '';
    }

    public function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
        finfo_close($finfo);

        return $mime;
    }

    /**
     * @param  array<string, mixed>  $storedFileData
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildAudioRecordPayload(array $storedFileData, array $data, ?int $uploadedBy = null): array
    {
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        if (! empty($data['title'])) {
            $meta['title'] = (string) $data['title'];
        }

        if (! empty($data['description'])) {
            $meta['description'] = (string) $data['description'];
        }

        return array_merge($storedFileData, [
            'processing_status' => ListeningAudioProcessingStatus::Pending,
            'validation_status' => ListeningAudioValidationStatus::Pending,
            'validation_errors' => null,
            'uploaded_by' => $uploadedBy,
            'meta' => $meta !== [] ? $meta : null,
            'retry_count' => 0,
        ]);
    }
}
