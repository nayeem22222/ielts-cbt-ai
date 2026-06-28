<?php

declare(strict_types=1);

namespace App\Support\Listening;

use App\Models\Listening\ListeningQuestionGroup;

final class ListeningGroupInteraction
{
    public const MODE_SELECT = 'select';

    public const MODE_INPUT = 'input';

    public const MODE_DRAG_DROP = 'drag_drop';

    public static function mode(ListeningQuestionGroup $group): string
    {
        $settings = is_array($group->settings) ? $group->settings : [];
        $type = $group->question_type;

        if ($type?->isCompletionBuilderType()) {
            return (string) ($settings['interaction_mode'] ?? self::MODE_INPUT);
        }

        return (string) ($settings['interaction_mode'] ?? self::MODE_SELECT);
    }

    public static function allowReuse(ListeningQuestionGroup $group): bool
    {
        $settings = is_array($group->settings) ? $group->settings : [];

        return (bool) ($settings['allow_reuse']
            ?? ($group->options['allow_choice_reuse'] ?? false));
    }

    public static function usesDragDrop(ListeningQuestionGroup $group): bool
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
    public static function mergeSettings(ListeningQuestionGroup $group, array $incoming): array
    {
        $settings = is_array($group->settings) ? $group->settings : [];

        if (array_key_exists('interaction_mode', $incoming)) {
            $settings['interaction_mode'] = (string) $incoming['interaction_mode'];
        }

        if (array_key_exists('allow_reuse', $incoming)) {
            $settings['allow_reuse'] = (bool) $incoming['allow_reuse'];
        }

        return $settings;
    }
}
