@php
    $selected = $selected ?? old('question_type', $group->question_type?->value ?? 'form_completion');
    $schemas = collect($questionTypeSchemas ?? [])->keyBy('type');
@endphp

<div class="md:col-span-2">
    <x-ui.select name="question_type" label="Question Type" required x-model="type" @change="applyTypeDefaults()">
        @foreach ($enabledQuestionTypes ?? $questionTypes as $type)
            <option value="{{ $type->value }}" @selected($selected === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>

    <div class="mt-2 text-xs aa-muted">
        @foreach ($schemas as $schema)
            <p x-show="type === @js($schema['type'])" x-cloak>
                Layout: {{ $schema['default_layout'] }} · Answer format: {{ $schema['default_answer_format'] }}
                @if (! empty($schema['required_group_fields']))
                    · Requires: {{ implode(', ', $schema['required_group_fields']) }}
                @endif
            </p>
        @endforeach
    </div>
</div>
