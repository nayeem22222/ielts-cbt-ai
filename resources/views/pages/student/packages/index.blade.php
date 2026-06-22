<x-layouts.dashboard heading="My Packages" eyebrow="Enrollment">
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Active Packages">
            <div class="space-y-4">
                @forelse ($activePackages as $enrollment)
                    <div class="rounded-2xl border border-neutral-100 p-4 dark:border-neutral-800">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold">{{ $enrollment->package->name }}</h3>
                                <p class="text-sm aa-muted">{{ $enrollment->package->description }}</p>
                            </div>
                            <x-ui.badge tone="green">{{ $enrollment->status->label() }}</x-ui.badge>
                        </div>
                        <p class="mt-3 text-sm aa-muted">
                            Expires: {{ $enrollment->expires_at?->format('M j, Y') ?? 'No expiry' }}
                        </p>
                    </div>
                @empty
                    <p class="aa-muted">No active packages yet.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Available Packages">
            <div class="space-y-4">
                @foreach ($availablePackages as $package)
                    <div class="rounded-2xl border border-neutral-100 p-4 dark:border-neutral-800">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="font-semibold">{{ $package->name }}</h3>
                                <p class="text-sm aa-muted">{{ number_format((float) $package->price, 0) }} {{ $package->currency }}</p>
                            </div>
                            <form method="POST" action="{{ route('student.packages.enroll', $package) }}">
                                @csrf
                                <x-ui.button size="sm">Activate</x-ui.button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    @if ($accessibleModules !== [])
        <x-ui.card title="Accessible Modules" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach ($accessibleModules as $module)
                    <x-ui.badge tone="blue">{{ ucfirst($module) }}</x-ui.badge>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</x-layouts.dashboard>
