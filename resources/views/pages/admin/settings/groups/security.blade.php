<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="session_lifetime" type="number" label="Session Lifetime (minutes)" :value="old('session_lifetime', $values['session_lifetime'] ?? 120)" required />
    <x-ui.input name="password_min_length" type="number" label="Minimum Password Length" :value="old('password_min_length', $values['password_min_length'] ?? 8)" required />
    <x-ui.input name="max_login_attempts" type="number" label="Max Login Attempts" :value="old('max_login_attempts', $values['max_login_attempts'] ?? 5)" required />
    <x-ui.input name="lockout_minutes" type="number" label="Lockout Duration (minutes)" :value="old('lockout_minutes', $values['lockout_minutes'] ?? 15)" required />
    <div class="md:col-span-2 space-y-2">
        <x-ui.checkbox name="force_email_verification" value="1" :checked="old('force_email_verification', $values['force_email_verification'] ?? true)">Require email verification</x-ui.checkbox>
        <x-ui.checkbox name="require_2fa_admins" value="1" :checked="old('require_2fa_admins', $values['require_2fa_admins'] ?? false)">Require 2FA for admins</x-ui.checkbox>
    </div>
    <x-ui.textarea name="ip_allowlist" label="IP Allowlist" class="md:col-span-2" rows="3" help="One IP per line. Leave empty to allow all.">{{ old('ip_allowlist', $values['ip_allowlist'] ?? '') }}</x-ui.textarea>
</div>
