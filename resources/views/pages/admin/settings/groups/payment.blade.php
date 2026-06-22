<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="default_gateway" label="Default Gateway">
        @foreach (['sslcommerz' => 'SSLCommerz', 'stripe' => 'Stripe', 'manual' => 'Manual'] as $value => $label)
            <option value="{{ $value }}" @selected(old('default_gateway', $values['default_gateway'] ?? 'sslcommerz') === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="currency" label="Currency" :value="old('currency', $values['currency'] ?? 'BDT')" required />
    <x-ui.input name="invoice_prefix" label="Invoice Prefix" :value="old('invoice_prefix', $values['invoice_prefix'] ?? 'INV')" required />
    <x-ui.input name="stripe_public_key" label="Stripe Public Key" :value="old('stripe_public_key', $values['stripe_public_key'] ?? '')" />
    <x-ui.input name="stripe_secret_key" type="password" label="Stripe Secret Key" :value="old('stripe_secret_key', '')" :help="($values['stripe_secret_key_configured'] ?? false) ? 'Configured. Leave blank to keep current value.' : null" />
    <x-ui.input name="webhook_secret" type="password" label="Webhook Secret" :value="old('webhook_secret', '')" :help="($values['webhook_secret_configured'] ?? false) ? 'Configured. Leave blank to keep current value.' : null" />
    <div class="md:col-span-2">
        <x-ui.checkbox name="sandbox_mode" value="1" :checked="old('sandbox_mode', $values['sandbox_mode'] ?? true)">Sandbox mode</x-ui.checkbox>
    </div>
</div>
