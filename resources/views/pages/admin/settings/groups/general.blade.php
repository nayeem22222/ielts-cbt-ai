<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="site_name" label="Site Name" :value="old('site_name', $values['site_name'] ?? '')" required />
    <x-ui.input name="support_email" type="email" label="Support Email" :value="old('support_email', $values['support_email'] ?? '')" required />
    <x-ui.input name="site_tagline" label="Site Tagline" class="md:col-span-2" :value="old('site_tagline', $values['site_tagline'] ?? '')" />
    <x-ui.input name="default_locale" label="Default Locale" :value="old('default_locale', $values['default_locale'] ?? 'en')" required />
    <x-ui.input name="timezone" label="Timezone" :value="old('timezone', $values['timezone'] ?? 'Asia/Dhaka')" required />
    <div class="md:col-span-2">
        <x-ui.checkbox name="maintenance_mode" value="1" :checked="old('maintenance_mode', $values['maintenance_mode'] ?? false)">Enable maintenance mode</x-ui.checkbox>
    </div>
    <x-ui.textarea name="maintenance_message" label="Maintenance Message" class="md:col-span-2" rows="3">{{ old('maintenance_message', $values['maintenance_message'] ?? '') }}</x-ui.textarea>
</div>
