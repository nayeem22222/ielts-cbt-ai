<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="preferred_driver" label="Preferred Driver">
        @foreach (['database' => 'Database', 'redis' => 'Redis', 'sync' => 'Sync (development)'] as $value => $label)
            <option value="{{ $value }}" @selected(old('preferred_driver', $values['preferred_driver'] ?? 'database') === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="retry_after" type="number" label="Retry After (seconds)" :value="old('retry_after', $values['retry_after'] ?? 90)" required />
    <x-ui.input name="max_tries" type="number" label="Max Tries" :value="old('max_tries', $values['max_tries'] ?? 3)" required />
    <div class="md:col-span-2">
        <x-ui.checkbox name="failed_job_alerts" value="1" :checked="old('failed_job_alerts', $values['failed_job_alerts'] ?? true)">Alert on failed jobs</x-ui.checkbox>
    </div>
</div>
