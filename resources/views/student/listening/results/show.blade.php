<x-layouts.dashboard :title="$test?->title ?? 'Listening Result'" :heading="$test?->title ?? 'Listening Result'">
  @include('student.listening.results.partials.score-card')
  @include('student.listening.results.partials.band-card')

  <div class="mt-6 grid gap-6 lg:grid-cols-2">
    @include('student.listening.results.partials.section-breakdown')
    @include('student.listening.results.partials.question-type-breakdown')
  </div>

  <div class="mt-6">
    @include('student.listening.results.partials.question-summary')
  </div>

  <div class="mt-6">
    <x-ui.button href="{{ route('student.listening.results.index') }}">Back to results</x-ui.button>
  </div>
</x-layouts.dashboard>
