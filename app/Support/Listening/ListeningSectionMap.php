<?php

declare(strict_types=1);

namespace App\Support\Listening;

use App\Enums\Listening\ListeningConstants;
use App\Enums\Listening\ListeningSectionType;
use InvalidArgumentException;

final class ListeningSectionMap
{
    /**
     * @return array<int, array{start: int, end: int, total: int, default_type: ListeningSectionType}>
     */
    public static function sectionRangeMap(): array
    {
        return [
            1 => [
                'start' => 1,
                'end' => 10,
                'total' => 10,
                'default_type' => ListeningSectionType::Conversation,
            ],
            2 => [
                'start' => 11,
                'end' => 20,
                'total' => 10,
                'default_type' => ListeningSectionType::Monologue,
            ],
            3 => [
                'start' => 21,
                'end' => 30,
                'total' => 10,
                'default_type' => ListeningSectionType::AcademicDiscussion,
            ],
            4 => [
                'start' => 31,
                'end' => 40,
                'total' => 10,
                'default_type' => ListeningSectionType::Lecture,
            ],
        ];
    }

    /**
     * @return array{start: int, end: int, total: int, default_type: ListeningSectionType}
     */
    public static function forSectionNumber(int $sectionNumber): array
    {
        $map = self::sectionRangeMap();

        if (! isset($map[$sectionNumber])) {
            throw new InvalidArgumentException("Invalid listening section number: {$sectionNumber}");
        }

        return $map[$sectionNumber];
    }

    public static function isValidSectionNumber(int $sectionNumber): bool
    {
        return $sectionNumber >= ListeningConstants::MIN_SECTION_NUMBER
            && $sectionNumber <= ListeningConstants::MAX_SECTION_NUMBER;
    }

    /**
     * @return list<int>
     */
    public static function officialSectionNumbers(): array
    {
        return array_keys(self::sectionRangeMap());
    }
}
