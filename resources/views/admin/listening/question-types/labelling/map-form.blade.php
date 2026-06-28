@php
    $options = old('options', $group->options ?? ['image' => ['path' => $group->image_path ?? '', 'alt' => $group->image_alt ?? ''], 'labels' => [['key' => 'A', 'text' => '']], 'points' => [['number' => $group->start_question_number ?? 1, 'x' => 50, 'y' => 50]]]);
    if (is_string($options)) { $options = json_decode($options, true) ?: []; }
@endphp
@include('admin.listening.question-types.labelling._labelling-form', ['options' => $options, 'label' => 'Map'])
