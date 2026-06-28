@php
    $selected = $selected ?? old('question_type', $group->question_type?->value ?? 'form_completion');
@endphp

<div id="listening-type-specific-form" class="contents">
    @foreach ($enabledQuestionTypes ?? $questionTypes as $questionType)
        @php $partial = app(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class)->formPartialFor($questionType); @endphp
        <div x-show="type === @js($questionType->value)" x-cloak class="md:col-span-2 space-y-4">
            @includeIf($partial, ['group' => $group, 'section' => $section, 'questionType' => $questionType])
        </div>
    @endforeach
</div>
