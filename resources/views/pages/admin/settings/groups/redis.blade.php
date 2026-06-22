<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="key_prefix" label="Key Prefix" :value="old('key_prefix', $values['key_prefix'] ?? 'ielts_cbt')" />
    <div class="md:col-span-2 space-y-2">
        <x-ui.checkbox name="use_for_cache" value="1" :checked="old('use_for_cache', $values['use_for_cache'] ?? false)">Prefer Redis for cache</x-ui.checkbox>
        <x-ui.checkbox name="use_for_queue" value="1" :checked="old('use_for_queue', $values['use_for_queue'] ?? false)">Prefer Redis for queue</x-ui.checkbox>
        <x-ui.checkbox name="use_for_session" value="1" :checked="old('use_for_session', $values['use_for_session'] ?? false)">Prefer Redis for sessions</x-ui.checkbox>
    </div>
</div>
