@props([
    'transcripts',
    'section',
    'name' => 'transcript_id',
    'attachMode' => false,
    'listeningTest' => null,
])

@php
    $sectionAudioId = $section->audio_id ?? null;
    $selectedId = (string) old($name, $section->transcript_id ?? '');
    $createUrl = route('admin.listening.transcripts.create', array_filter([
        'listening_audio_id' => $sectionAudioId,
        'title' => ($section->title ?? 'Section').' Transcript',
        'return' => $listeningTest
            ? route('admin.listening.tests.sections.show', [$listeningTest, $section])
            : null,
    ]));

    $recommended = collect();
    $others = collect();

    foreach ($transcripts as $transcript) {
        $audioMismatch = $sectionAudioId
            && $transcript->listening_audio_id
            && (int) $sectionAudioId !== (int) $transcript->listening_audio_id;

        $isRecommended = $sectionAudioId === null
            || $transcript->listening_audio_id === null
            || (int) $transcript->listening_audio_id === (int) $sectionAudioId;

        $item = [
            'model' => $transcript,
            'audio_mismatch' => $audioMismatch,
        ];

        if ($isRecommended) {
            $recommended->push($item);
        } else {
            $others->push($item);
        }
    }

    $options = $transcripts->map(function ($transcript) use ($sectionAudioId) {
        $audioMismatch = $sectionAudioId
            && $transcript->listening_audio_id
            && (int) $sectionAudioId !== (int) $transcript->listening_audio_id;

        $recommended = $sectionAudioId === null
            || $transcript->listening_audio_id === null
            || (int) $transcript->listening_audio_id === (int) $sectionAudioId;

        return [
            'id' => (int) $transcript->id,
            'title' => $transcript->title ?: 'Transcript #'.$transcript->id,
            'preview' => \Illuminate\Support\Str::limit(trim((string) $transcript->transcript_text), 220),
            'visibility_label' => $transcript->visibility?->label() ?? 'Unknown',
            'is_official' => (bool) $transcript->is_official,
            'audio_mismatch' => $audioMismatch,
            'recommended' => $recommended,
            'has_timestamps' => is_array($transcript->timestamped_transcript) && $transcript->timestamped_transcript !== [],
        ];
    })->values();
@endphp

<div
    class="space-y-4"
    x-data="{
        selected: @js($selectedId),
        search: '',
        options: @js($options),
        sectionAudioId: @js($sectionAudioId),
        get filtered() {
            const q = this.search.trim().toLowerCase();
            if (! q) return this.options;
            return this.options.filter((item) =>
                item.title.toLowerCase().includes(q) || item.preview.toLowerCase().includes(q)
            );
        },
        get current() {
            return this.options.find((item) => String(item.id) === String(this.selected)) ?? null;
        },
        get showMismatchWarning() {
            return this.current?.audio_mismatch === true;
        }
    }"
>
    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 text-sm text-blue-900 dark:border-blue-900/40 dark:bg-blue-950/30 dark:text-blue-100">
        <p class="font-medium">Admin reference only</p>
        <p class="mt-1 text-blue-800/90 dark:text-blue-200/90">Transcripts help you write questions and review answers. They are never shown to students during the live listening test.</p>
    </div>

    @if ($transcripts->isEmpty())
        <x-ui.empty-state title="No transcripts yet">
            Create a transcript for this section, then attach it here in one click.
            @can('create', \App\Models\Listening\ListeningTranscript::class)
                <div class="mt-4">
                    <x-ui.button href="{{ $createUrl }}">Create Transcript for This Section</x-ui.button>
                </div>
            @endcan
        </x-ui.empty-state>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-3">
                <x-ui.input
                    type="search"
                    label="Search transcripts"
                    placeholder="Search by title or text..."
                    x-model="search"
                />

                <div>
                    <label class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200" for="{{ $name }}">Choose transcript</label>
                    <select
                        id="{{ $name }}"
                        name="{{ $name }}"
                        x-model="selected"
                        class="w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <option value="">No transcript attached</option>
                        @if ($recommended->isNotEmpty())
                            <optgroup label="Recommended for this section">
                                @foreach ($recommended as $item)
                                    @php $transcript = $item['model']; @endphp
                                    <option
                                        value="{{ $transcript->id }}"
                                        @selected($selectedId === (string) $transcript->id)
                                    >
                                        {{ $transcript->title ?: 'Transcript #'.$transcript->id }}
                                        @if ($transcript->is_official) · Official @endif
                                        @if (is_array($transcript->timestamped_transcript) && $transcript->timestamped_transcript !== []) · Timestamps @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if ($others->isNotEmpty())
                            <optgroup label="Other transcripts">
                                @foreach ($others as $item)
                                    @php $transcript = $item['model']; @endphp
                                    <option
                                        value="{{ $transcript->id }}"
                                        @selected($selectedId === (string) $transcript->id)
                                    >
                                        {{ $transcript->title ?: 'Transcript #'.$transcript->id }}
                                        @if ($item['audio_mismatch']) · Audio mismatch @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <p class="mt-1 text-xs aa-muted" x-show="sectionAudioId">Transcripts linked to this section's audio appear first.</p>

                    <div class="mt-3 space-y-1" x-show="search.trim() !== '' && filtered.length" x-cloak>
                        <p class="text-xs font-medium aa-muted">Quick picks from search</p>
                        <template x-for="item in filtered.slice(0, 5)" :key="'pick-' + item.id">
                            <button
                                type="button"
                                class="block w-full rounded-lg border border-neutral-200 px-3 py-2 text-left text-sm hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900"
                                @click="selected = String(item.id)"
                            >
                                <span class="font-medium" x-text="item.title"></span>
                                <span class="mt-0.5 block text-xs aa-muted" x-text="item.preview"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @can('create', \App\Models\Listening\ListeningTranscript::class)
                        <x-ui.button href="{{ $createUrl }}" variant="outline" size="sm">+ Create New Transcript</x-ui.button>
                    @endcan
                    <x-ui.button href="{{ route('admin.listening.transcripts.index') }}" variant="outline" size="sm">Browse All</x-ui.button>
                </div>

                @if ($attachMode && config('listening.transcript.strict_audio_match', true))
                    <div x-show="showMismatchWarning" x-cloak>
                        <x-ui.alert tone="amber" title="Audio mismatch">
                            This transcript is linked to different audio. Enable force attach only if you are sure.
                        </x-ui.alert>
                        <div class="mt-2">
                            <x-ui.checkbox name="force_attach" label="Force attach (admin override)" />
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                <p class="mb-2 text-sm font-medium text-neutral-900 dark:text-white">Preview</p>
                <template x-if="current">
                    <div class="space-y-3 text-sm">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800" x-text="current.visibility_label"></span>
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="current.is_official ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-neutral-100 dark:bg-neutral-800'" x-text="current.is_official ? 'Official' : 'Custom'"></span>
                            <span x-show="current.has_timestamps" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">Timestamped</span>
                            <span x-show="current.audio_mismatch" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Audio mismatch</span>
                        </div>
                        <p class="whitespace-pre-wrap aa-muted" x-text="current.preview || 'No preview text available.'"></p>
                        <a
                            class="inline-flex text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                            :href="`{{ url('/admin/listening/transcripts') }}/${current.id}`"
                        >Open full transcript</a>
                    </div>
                </template>
                <template x-if="! current">
                    <p class="text-sm aa-muted">Select a transcript to see a preview here.</p>
                </template>
            </div>
        </div>
    @endif
</div>
