<x-layouts.admin title="{{ $listeningTest->title }}" heading="{{ $listeningTest->title }}" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title]]">
  @if (session('error'))
    <x-ui.alert tone="red" class="mb-4">{{ session('error') }}</x-ui.alert>
  @endif

  @if (session('publish_errors'))
    <x-ui.alert tone="red" class="mb-4">
      <p class="font-semibold">Publishing blocked</p>
      <ul class="mt-2 list-disc pl-5">
        @foreach (session('publish_errors') as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </x-ui.alert>
  @endif

  <div class="mb-6 flex flex-wrap justify-between gap-4">
    <div>
      <div class="mb-2">@include('admin.listening.tests.partials.status-badge', ['status' => $listeningTest->status])</div>
      <p class="text-sm aa-muted">{{ $listeningTest->test_code }} · {{ $listeningTest->test_type?->label() }} · {{ $listeningTest->difficulty_level?->label() }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
      @can('update', $listeningTest)
        <x-ui.button href="{{ route($routePrefix.'.edit', $listeningTest) }}">Edit Test</x-ui.button>
      @endcan
      @can('publish', $listeningTest)
        @if ($listeningTest->status === \App\Enums\Listening\ListeningTestStatus::Published)
          <form method="POST" action="{{ route($routePrefix.'.unpublish', $listeningTest) }}">@csrf<x-ui.button type="submit" variant="outline">Unpublish</x-ui.button></form>
        @else
          <form method="POST" action="{{ route($routePrefix.'.publish', $listeningTest) }}">@csrf<x-ui.button type="submit" variant="outline">Publish</x-ui.button></form>
        @endif
      @endcan
      @can('archive', $listeningTest)
        @if ($listeningTest->status !== \App\Enums\Listening\ListeningTestStatus::Archived)
          <form method="POST" action="{{ route($routePrefix.'.archive', $listeningTest) }}">@csrf<x-ui.button type="submit" variant="outline">Archive</x-ui.button></form>
        @endif
      @endcan
      @can('duplicate', $listeningTest)
        <form method="POST" action="{{ route($routePrefix.'.duplicate', $listeningTest) }}">@csrf<x-ui.button type="submit" variant="outline">Duplicate</x-ui.button></form>
      @endcan
      @can('delete', $listeningTest)
        <form method="POST" action="{{ route($routePrefix.'.destroy', $listeningTest) }}" onsubmit="return confirm('Delete this listening test?')">
          @csrf @method('DELETE')
          <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
        </form>
      @endcan
    </div>
  </div>

  <div class="grid gap-6 xl:grid-cols-2">
    <x-ui.card title="Test Information">
      <dl class="space-y-3 text-sm">
        <div><dt class="aa-muted">Slug</dt><dd>{{ $listeningTest->slug }}</dd></div>
        <div><dt class="aa-muted">Duration</dt><dd>{{ $listeningTest->duration_minutes }} minutes + {{ $listeningTest->transfer_time_minutes }} transfer</dd></div>
        <div><dt class="aa-muted">Active</dt><dd>{{ $listeningTest->is_active ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="aa-muted">Featured</dt><dd>{{ $listeningTest->is_featured ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="aa-muted">Published At</dt><dd>{{ $listeningTest->published_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        <div><dt class="aa-muted">Created By</dt><dd>{{ $listeningTest->createdBy?->name ?? '—' }}</dd></div>
        @if ($listeningTest->description)
          <div><dt class="aa-muted">Description</dt><dd>{{ $listeningTest->description }}</dd></div>
        @endif
        @if ($listeningTest->instructions)
          <div><dt class="aa-muted">Instructions</dt><dd class="whitespace-pre-wrap">{{ $listeningTest->instructions }}</dd></div>
        @endif
      </dl>
    </x-ui.card>

    @include('admin.listening.tests.partials.readiness-card')
  </div>

  <div class="mt-6 grid gap-6 lg:grid-cols-3">
    <x-ui.card title="Sections">
      <p class="text-sm aa-muted">Manage the 4 official IELTS Listening sections for this test.</p>
      @can('viewAny', [App\Models\Listening\ListeningSection::class, $listeningTest])
        <x-ui.button class="mt-4" href="{{ route('admin.listening.tests.sections.index', $listeningTest) }}">Manage Sections</x-ui.button>
      @endcan
    </x-ui.card>
    <x-ui.card title="Audio">
      <p class="text-sm aa-muted">Audio management will be available in a later volume.</p>
      <x-ui.button class="mt-4" variant="outline" disabled>Manage Audio</x-ui.button>
    </x-ui.card>
    <x-ui.card title="Questions">
      <p class="text-sm aa-muted">Build question groups and individual questions for all sections.</p>
      <x-ui.button class="mt-4" href="{{ route('admin.listening.tests.builder.index', $listeningTest) }}">Question Builder</x-ui.button>
    </x-ui.card>
  </div>

  <div class="mt-6">
    <x-ui.button variant="outline" disabled>Preview Test</x-ui.button>
  </div>

  <div class="mt-6">
    @include('admin.listening.tests.partials.settings-form')
  </div>
</x-layouts.admin>
