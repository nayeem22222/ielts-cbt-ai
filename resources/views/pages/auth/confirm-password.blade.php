<x-layouts.guest title="Confirm Password">
<div class="auth-page">
    <form class="auth-card" method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        @if($errors->any())<div class="alert-demo" style="background:#fef3f2;color:#b42318">{{ $errors->first() }}</div>@endif
        <h1>Confirm password</h1>
        <p>This is a secure area. Please confirm your password to continue.</p>
        <div class="field">
            <label>Password</label>
            <input name="password" type="password" required placeholder="••••••••">
            @error('password')<small style="color:#b42318">{{ $message }}</small>@enderror
        </div>
        <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Confirm</button>
    </form>
</div>
</x-layouts.guest>
