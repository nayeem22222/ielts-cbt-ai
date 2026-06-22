@props([
    'name' => 'content',
    'label' => null,
    'value' => '',
    'rows' => 12,
    'placeholder' => 'Write rich content here...',
    'required' => false,
])

<div {{ $attributes->class(['space-y-2']) }} x-data="{ content: @js(old($name, $value)) }">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">{{ $label }}</label>
    @endif

    <div class="overflow-hidden rounded-3xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-wrap gap-2 border-b border-neutral-200 p-3 dark:border-neutral-800">
            @foreach (['Bold', 'Italic', 'Underline', 'List', 'Numbered', 'Link', 'Clear'] as $button)
                <button type="button" class="rounded-xl px-3 py-1.5 text-xs font-semibold text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">{{ $button }}</button>
            @endforeach
        </div>
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if ($required) required @endif
            x-model="content"
            class="min-h-48 w-full resize-y bg-transparent p-5 text-sm leading-7 outline-none dark:text-neutral-100"
            placeholder="{{ $placeholder }}"
        >{{ old($name, $value) }}</textarea>
    </div>
</div>
