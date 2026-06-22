<x-layouts.guest title="Verify Email">
<div class="auth-page">
    <div class="auth-card">
        @if(session('status'))<div class="alert-demo">{{ session('status') }}</div>@endif
        <h1>Verify your email</h1>
        <p>Thanks for signing up. Please check your inbox for a verification link before accessing your dashboard.</p>
        <p style="margin-top:16px" class="aa-muted">Signed in as <strong>{{ auth()->user()->email }}</strong></p>
        <form method="POST" action="{{ route('verification.send') }}" style="margin-top:24px">
            @csrf
            <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" style="margin-top:12px">
            @csrf
            <button type="submit" style="width:100%;padding:12px;border:1px solid #e4e7ec;border-radius:12px;background:#fff;cursor:pointer">Logout</button>
        </form>
    </div>
</div>
</x-layouts.guest>
