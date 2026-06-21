<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class DataTransferObject
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(...static::mapFromArray($data));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mapFromArray(array $data): array
    {
        return $data;
    }
}
