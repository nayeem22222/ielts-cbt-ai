<?php

declare(strict_types=1);

namespace App\Crud;

use Illuminate\Http\Request;

final class CrudQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly ?string $search = null,
        public readonly array $filters = [],
        public readonly ?string $sort = null,
        public readonly string $direction = 'desc',
        public readonly int $page = 1,
        public readonly ?int $perPage = null,
        public readonly bool $onlyTrashed = false,
    ) {
    }

    public static function fromRequest(Request $request, CrudDefinition $definition): self
    {
        $direction = strtolower((string) $request->query('direction', $definition->defaultDirection));

        return new self(
            search: self::nullableString($request->query('search')),
            filters: self::extractFilters($request, $definition),
            sort: self::nullableString($request->query('sort')),
            direction: in_array($direction, ['asc', 'desc'], true) ? $direction : $definition->defaultDirection,
            page: max(1, (int) $request->query('page', 1)),
            perPage: $request->filled('per_page') ? max(1, (int) $request->query('per_page')) : null,
            onlyTrashed: $request->boolean('trashed'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractFilters(Request $request, CrudDefinition $definition): array
    {
        $filters = [];

        foreach (array_keys($definition->filters) as $key) {
            $value = $request->query($key);

            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
