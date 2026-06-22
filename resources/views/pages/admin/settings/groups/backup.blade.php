<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="schedule" label="Schedule">
        @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
            <option value="{{ $value }}" @selected(old('schedule', $values['schedule'] ?? 'daily') === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="storage_disk" label="Storage Disk">
        @foreach (['local' => 'Local', 'public' => 'Public', 's3' => 'Amazon S3'] as $value => $label)
            <option value="{{ $value }}" @selected(old('storage_disk', $values['storage_disk'] ?? 'local') === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="retention_days" type="number" label="Retention (days)" :value="old('retention_days', $values['retention_days'] ?? 14)" required />
    <x-ui.input name="notify_email" type="email" label="Notification Email" :value="old('notify_email', $values['notify_email'] ?? '')" />
    <div class="md:col-span-2">
        <x-ui.checkbox name="enabled" value="1" :checked="old('enabled', $values['enabled'] ?? true)">Enable scheduled backups</x-ui.checkbox>
    </div>
</div>
