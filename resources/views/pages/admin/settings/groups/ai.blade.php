<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="default_provider" label="Default Provider">
        @foreach (['openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'azure' => 'Azure OpenAI'] as $value => $label)
            <option value="{{ $value }}" @selected(old('default_provider', $values['default_provider'] ?? 'openai') === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="default_model" label="Default Model" :value="old('default_model', $values['default_model'] ?? '')" required />
    <x-ui.input name="api_key" type="password" label="API Key" :value="old('api_key', '')" :help="($values['api_key_configured'] ?? false) ? 'A key is configured. Leave blank to keep the current key.' : 'Enter your provider API key.'" />
    <x-ui.input name="max_tokens" type="number" label="Max Tokens" :value="old('max_tokens', $values['max_tokens'] ?? 4096)" required />
    <x-ui.input name="temperature" type="number" step="0.1" label="Temperature" :value="old('temperature', $values['temperature'] ?? 0.7)" required />
    <x-ui.input name="request_timeout" type="number" label="Request Timeout (seconds)" :value="old('request_timeout', $values['request_timeout'] ?? 60)" required />
    <div class="md:col-span-2">
        <x-ui.checkbox name="evaluation_enabled" value="1" :checked="old('evaluation_enabled', $values['evaluation_enabled'] ?? true)">Enable AI evaluation pipeline</x-ui.checkbox>
    </div>
</div>
