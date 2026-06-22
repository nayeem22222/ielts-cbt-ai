<x-layouts.admin title="Enrollments" heading="Student Enrollments" eyebrow="Commerce" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Enrollments']]">
    <div class="mb-6 grid gap-6 xl:grid-cols-[1fr_.8fr]">
        <x-ui.card title="Assign Package">
            <form method="POST" action="{{ route('admin.enrollments.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <x-ui.select name="user_id" label="Student" required>
                    <option value="">Select student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="package_id" label="Package" required>
                    <option value="">Select package</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected(old('package_id') == $package->id)>{{ $package->name }}</option>
                    @endforeach
                </x-ui.select>
                <div class="md:col-span-2">
                    <x-ui.button type="submit">Enroll & Activate</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Summary">
            <p class="text-sm aa-muted">Assign packages to students, activate subscriptions, and provision linked courses automatically.</p>
        </x-ui.card>
    </div>

    <x-ui.card title="Enrollment Records">
        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4">Student</th>
                    <th class="p-4">Package</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Expires</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($enrollments as $enrollment)
                    <tr>
                        <td class="p-4">{{ $enrollment->user->name }}</td>
                        <td class="p-4">{{ $enrollment->package->name }}</td>
                        <td class="p-4"><x-ui.badge>{{ $enrollment->status->label() }}</x-ui.badge></td>
                        <td class="p-4">{{ $enrollment->expires_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.enrollments.activate', $enrollment) }}">
                                    @csrf
                                    @method('PUT')
                                    <x-ui.button size="sm" variant="outline">Activate</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admin.enrollments.cancel', $enrollment) }}">
                                    @csrf
                                    @method('PUT')
                                    <x-ui.button size="sm" variant="outline">Cancel</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-4 aa-muted" colspan="5">No enrollments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">{{ $enrollments->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
