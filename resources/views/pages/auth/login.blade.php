<x-layouts.guest title="Student Login">
<div class="auth-page">
    <form class="auth-card" method="POST" action="{{ route('login.store') }}">
        @csrf
        @if(session('status'))<div class="alert-demo">{{ session('status') }}</div>@endif
        <h1>Welcome back</h1>
        <p>Login to continue your IELTS preparation.</p>
        <div class="field"><label>Email</label><input name="email" type="email" value="{{ old('email') }}" required placeholder="student@example.com"></div>
        <div class="field"><label>Password</label><input name="password" type="password" required placeholder="••••••••"></div>
        <div class="auth-meta"><label><input type="checkbox" name="remember"> Remember me</label><a class="auth-link" href="/forgot-password">Forgot password?</a></div>
        <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Login</button>
        <p style="margin-top:20px;text-align:center">New student? <a class="auth-link" href="/register">Create account</a></p>
    </form>
</div>
</x-layouts.guest>
