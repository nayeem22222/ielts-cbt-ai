@props(['group', 'mode' => 'matching'])

@php
    use App\Support\Reading\ReadingGroupInteraction;

    $settings = $group->settings ?? [];
    $interactionMode = ReadingGroupInteraction::mode($group);
    $allowReuse = ReadingGroupInteraction::allowReuse($group);
    $modes = $mode === 'completion'
        ? ReadingGroupInteraction::completionInteractionModes()
        : ReadingGroupInteraction::matchingInteractionModes();
    $showAllowReuse = $mode === 'matching' || $group->question_type?->value === 'matching_headings';
@endphp

<x-ui.card title="Interaction Settings" class="mb-6">
    <form
        method="POST"
        action="{{ route('admin.reading-question-groups.interaction-settings.update', $group) }}"
        class="grid gap-4 md:grid-cols-2"
    >
        @csrf
        @method('PUT')

        <div>
            <label for="interaction_mode_{{ $group->id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Interaction Mode</label>
            <select
                id="interaction_mode_{{ $group->id }}"
                name="interaction_mode"
                class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            >
                @foreach ($modes as $modeValue)
                    <option value="{{ $modeValue }}" @selected($interactionMode === $modeValue)>
                        {{ match ($modeValue) {
                            'select' => 'Select / Dropdown',
                            'input' => 'Text Input',
                            'drag_drop' => 'Drag & Drop',
                            default => ucfirst(str_replace('_', ' ', $modeValue)),
                        } }}
                    </option>
                @endforeach
            </select>
            @if ($mode === 'completion')
                <p class="mt-1 text-xs aa-muted">Drag &amp; drop and select modes require word-bank options on the group.</p>
            @endif
        </div>

        @if ($showAllowReuse)
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-200">
                    <input
                        type="checkbox"
                        name="allow_reuse"
                        value="1"
                        class="rounded border-neutral-300"
                        @checked($allowReuse)
                    >
                    Allow option reuse across questions
                </label>
            </div>
        @endif

        <div class="md:col-span-2">
            <x-ui.button type="submit" variant="secondary">Save Interaction Settings</x-ui.button>
        </div>
    </form>
</x-ui.card>
