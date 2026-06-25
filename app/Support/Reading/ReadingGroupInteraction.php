<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingQuestionGroup;

final class ReadingGroupInteraction
{
    public const MODE_SELECT = 'select';

    public const MODE_INPUT = 'input';

    public const MODE_DRAG_DROP = 'drag_drop';

    public static function mode(ReadingQuestionGroup $group): string
    {
        $settings = $group->settings ?? [];
        $type = $group->question_type;

        if ($type?->isCompletionBuilderType()) {
            return (string) ($settings['interaction_mode'] ?? self::MODE_INPUT);
        }

        return (string) ($settings['interaction_mode'] ?? self::MODE_SELECT);
    }

    public static function allowReuse(ReadingQuestionGroup $group): bool
    {
        return (bool) ($group->settings['allow_reuse'] ?? false);
    }

    public static function usesDragDrop(ReadingQuestionGroup $group): bool
    {
        return self::mode($group) === self::MODE_DRAG_DROP;
    }

    /**
     * @return list<string>
     */
    public static function matchingInteractionModes(): array
    {
        return [self::MODE_SELECT, self::MODE_DRAG_DROP];
    }

    /**
     * @return list<string>
     */
    public static function completionInteractionModes(): array
    {
        return [self::MODE_INPUT, self::MODE_SELECT, self::MODE_DRAG_DROP];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergeSettings(ReadingQuestionGroup $group, array $incoming): array
    {
        $settings = $group->settings ?? [];

        if (array_key_exists('interaction_mode', $incoming)) {
            $settings['interaction_mode'] = (string) $incoming['interaction_mode'];
        }

        if (array_key_exists('allow_reuse', $incoming)) {
            $settings['allow_reuse'] = (bool) $incoming['allow_reuse'];
        }

        return $settings;
    }
}
