<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="default_disk" label="Default Disk">
        @foreach (['local' => 'Local', 'public' => 'Public', 's3' => 'Amazon S3'] as $value => $label)
            <option value="{{ $value }}" @selected(old('default_disk', $values['default_disk'] ?? 'local') === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="max_upload_mb" type="number" label="Max Upload (MB)" :value="old('max_upload_mb', $values['max_upload_mb'] ?? 25)" required />
    <x-ui.input name="allowed_file_types" label="Allowed File Types" class="md:col-span-2" :value="old('allowed_file_types', $values['allowed_file_types'] ?? '')" help="Comma-separated extensions" required />
    <div class="md:col-span-2">
        <x-ui.checkbox name="public_uploads" value="1" :checked="old('public_uploads', $values['public_uploads'] ?? false)">Allow public uploads</x-ui.checkbox>
    </div>
</div>
