<x-layouts.guest title="Forgot Password">
<div class="auth-page">
    <form class="auth-card" method="POST" action="{{ route('password.email') }}">
        @csrf
        @if(session('status'))<div class="alert-demo">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert-demo" style="background:#fef3f2;color:#b42318">{{ $errors->first() }}</div>@endif
        <h1>Reset password</h1>
        <p>Enter your email and we will send you a reset link.</p>
        <div class="field">
            <label>Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required placeholder="you@example.com">
            @error('email')<small style="color:#b42318">{{ $message }}</small>@enderror
        </div>
        <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Send reset link</button>
        <p style="margin-top:20px;text-align:center"><a class="auth-link" href="{{ route('login') }}">Back to login</a></p>
    </form>
</div>
</x-layouts.guest>
