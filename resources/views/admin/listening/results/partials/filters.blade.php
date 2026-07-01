<form method="GET" action="{{ route('admin.listening.results.index') }}" class="grid gap-3 md:grid-cols-4">
    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search code, student, test" class="rounded border px-3 py-2 text-sm" />
    <select name="user_id" class="rounded border px-3 py-2 text-sm">
        <option value="">All students</option>
        @foreach ($students as $student)
            <option value="{{ $student->id }}" @selected(($filters['user_id'] ?? '') == $student->id)>{{ $student->name }}</option>
        @endforeach
    </select>
    <select name="listening_test_id" class="rounded border px-3 py-2 text-sm">
        <option value="">All tests</option>
        @foreach ($tests as $test)
            <option value="{{ $test->id }}" @selected(($filters['listening_test_id'] ?? '') == $test->id)>{{ $test->title }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded border px-3 py-2 text-sm">
        <option value="">All statuses</option>
        @foreach (['pending', 'ready', 'failed', 'hidden'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <input type="number" step="0.5" name="band_score" value="{{ $filters['band_score'] ?? '' }}" placeholder="Band score" class="rounded border px-3 py-2 text-sm" />
    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded border px-3 py-2 text-sm" />
    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded border px-3 py-2 text-sm" />
    <select name="is_visible_to_student" class="rounded border px-3 py-2 text-sm">
        <option value="">Visibility</option>
        <option value="1" @selected(($filters['is_visible_to_student'] ?? '') === '1')>Visible</option>
        <option value="0" @selected(($filters['is_visible_to_student'] ?? '') === '0')>Hidden</option>
    </select>
    <div class="flex gap-2">
        <x-ui.button type="submit">Filter</x-ui.button>
        <x-ui.button variant="secondary" href="{{ route('admin.listening.results.index') }}">Reset</x-ui.button>
    </div>
</form>
