<?php

declare(strict_types=1);

namespace App\Support\Reading;

final class ReadingValidationIssue
{
    /**
     * @return array{
     *     type: string,
     *     message: string,
     *     entity: string,
     *     entity_id: int|string|null,
     *     suggested_fix: string,
     *     section_link: ?string
     * }
     */
    public static function make(
        string $type,
        string $message,
        string $entity,
        int|string|null $entityId,
        string $suggestedFix,
        ?string $sectionLink = null,
    ): array {
        return [
            'type' => $type,
            'message' => $message,
            'entity' => $entity,
            'entity_id' => $entityId,
            'suggested_fix' => $suggestedFix,
            'section_link' => $sectionLink,
        ];
    }
}
