@props([
    'options',
    'group',
    'romanKeys' => true,
])

<ul class="reading-mh-pool" role="listbox" aria-label="List of headings">
    @foreach ($options as $option)
        <li class="reading-mh-pool__item">
            <button
                type="button"
                class="reading-dnd-token reading-mh-card"
                draggable="true"
                role="option"
                data-option-key="{{ $option->option_key }}"
                data-option-label="{{ $option->option_label }}"
                data-group-id="{{ $group->id }}"
                aria-grabbed="false"
            >
                <span class="reading-mh-card__key">{{ $option->option_key }}@if($romanKeys).@endif</span>
                <span class="reading-mh-card__label">{{ $option->option_label }}</span>
            </button>
        </li>
    @endforeach
</ul>
