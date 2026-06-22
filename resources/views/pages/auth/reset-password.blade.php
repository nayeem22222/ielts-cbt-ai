<x-layouts.guest title="Reset Password">
<div class="auth-page">
    <form class="auth-card" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if(session('status'))<div class="alert-demo">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert-demo" style="background:#fef3f2;color:#b42318">{{ $errors->first() }}</div>@endif
        <h1>Choose a new password</h1>
        <p>Enter your email and a new password below.</p>
        <div class="field">
            <label>Email</label>
            <input name="email" type="email" value="{{ old('email', $email) }}" required>
            @error('email')<small style="color:#b42318">{{ $message }}</small>@enderror
        </div>
        <div class="field">
            <label>New password</label>
            <input name="password" type="password" required placeholder="Minimum 8 characters">
            @error('password')<small style="color:#b42318">{{ $message }}</small>@enderror
        </div>
        <div class="field">
            <label>Confirm password</label>
            <input name="password_confirmation" type="password" required>
        </div>
        <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Reset password</button>
    </form>
</div>
</x-layouts.guest>
