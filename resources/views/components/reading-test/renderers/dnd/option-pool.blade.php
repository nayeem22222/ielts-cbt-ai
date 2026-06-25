@props([
    'options',
    'group',
    'romanKeys' => false,
])

<ul class="reading-dnd-pool reading-test-option-list" role="listbox" aria-label="Draggable options">
    @foreach ($options as $option)
        <li>
            <button
                type="button"
                class="reading-dnd-token"
                draggable="true"
                role="option"
                data-option-key="{{ $option->option_key }}"
                data-option-label="{{ $option->option_label }}"
                data-group-id="{{ $group->id }}"
                aria-grabbed="false"
            >
                <span class="reading-dnd-token__key">{{ $option->option_key }}@if($romanKeys).@endif</span>
                <span class="reading-dnd-token__label">{{ $option->option_label ?: '—' }}</span>
            </button>
        </li>
    @endforeach
</ul>
