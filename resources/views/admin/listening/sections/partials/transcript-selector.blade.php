@include('admin.listening.sections.partials.transcript-picker', [
    'transcripts' => $transcripts,
    'section' => $section,
    'listeningTest' => $listeningTest ?? null,
    'attachMode' => false,
])
