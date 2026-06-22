<x-layouts.guest title="Active Sessions">
<div class="auth-page">
    <div class="auth-card" style="max-width:720px">
        @if(session('status'))<div class="alert-demo">{{ session('status') }}</div>@endif
        <h1>Active sessions</h1>
        <p>Manage devices and browsers where you are signed in.</p>

        @if($sessions->isEmpty())
            <p class="aa-muted" style="margin-top:20px">No active sessions found.</p>
        @else
            <div style="margin-top:24px;display:grid;gap:12px">
                @foreach($sessions as $session)
                    <div style="border:1px solid #e4e7ec;border-radius:16px;padding:16px">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
                            <div>
                                <strong>{{ $session['is_current'] ? 'Current session' : 'Other session' }}</strong>
                                <p style="margin:6px 0 0;font-size:13px;color:#667085">{{ $session['ip_address'] ?? 'Unknown IP' }}</p>
                                <p style="margin:4px 0 0;font-size:12px;color:#667085">{{ Str::limit($session['user_agent'] ?? 'Unknown device', 80) }}</p>
                                <p style="margin:4px 0 0;font-size:12px;color:#667085">Last active {{ $session['last_activity']->diffForHumans() }}</p>
                            </div>
                            @if(! $session['is_current'])
                                <form method="POST" action="{{ route('account.sessions.destroy', $session['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="padding:8px 12px;border:1px solid #fda29b;border-radius:10px;background:#fff;color:#b42318;cursor:pointer">Revoke</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($sessions->where('is_current', false)->isNotEmpty())
            <form method="POST" action="{{ route('account.sessions.destroy-others') }}" style="margin-top:20px">
                @csrf
                @method('DELETE')
                <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Logout all other sessions</button>
            </form>
        @endif

        <p style="margin-top:20px;text-align:center"><a class="auth-link" href="{{ auth()->user()->dashboardPath() }}">Back to dashboard</a></p>
    </div>
</div>
</x-layouts.guest>
