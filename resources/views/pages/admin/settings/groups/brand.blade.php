<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="app_name" label="Application Name" :value="old('app_name', $values['app_name'] ?? '')" required />
    <x-ui.input name="primary_color" label="Primary Color" :value="old('primary_color', $values['primary_color'] ?? '#2563eb')" required />
    <x-ui.input name="secondary_color" label="Secondary Color" :value="old('secondary_color', $values['secondary_color'] ?? '#0f172a')" required />
    <x-ui.input name="logo_url" label="Logo URL" :value="old('logo_url', $values['logo_url'] ?? '')" />
    <x-ui.input name="favicon_url" label="Favicon URL" :value="old('favicon_url', $values['favicon_url'] ?? '')" />
    <x-ui.textarea name="footer_text" label="Footer Text" class="md:col-span-2" rows="2">{{ old('footer_text', $values['footer_text'] ?? '') }}</x-ui.textarea>
</div>
