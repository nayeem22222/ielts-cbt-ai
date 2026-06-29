@if (($payload['config']['show_section_tabs'] ?? true) === true)
    <nav class="border-b border-neutral-200 bg-white">
        <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-2" id="listening-section-tabs">
            @foreach ($payload['sections'] ?? [] as $section)
                @php
                    $active = (int) ($payload['current_section_number'] ?? 1) === (int) $section['number'];
                @endphp
                <button
                    type="button"
                    data-section="{{ $section['number'] }}"
                    class="listening-section-tab rounded-lg px-3 py-1.5 text-sm font-medium {{ $active ? 'bg-brand-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}"
                >
                    Part {{ $section['number'] }}
                </button>
            @endforeach
        </div>
    </nav>
@endif
