<x-layouts.guest title="Device Management">
<div class="auth-page">
    <div class="auth-card" style="max-width:820px">
        @if(session('status'))<div class="alert-demo">{{ session('status') }}</div>@endif
        <h1>Device management</h1>
        <p>Review browsers, operating systems, trusted devices, and active sessions.</p>

        <section style="margin-top:28px">
            <h2 style="font-size:18px;margin-bottom:8px">Trusted devices</h2>
            <p style="font-size:13px;color:#667085;margin-bottom:12px">Devices you have marked as trusted.</p>

            @if($trustedDevices->isEmpty())
                <p class="aa-muted">No trusted devices yet.</p>
            @else
                <div style="display:grid;gap:12px">
                    @foreach($trustedDevices as $device)
                        @php($record = $device['model'])
                        <div style="border:1px solid #12b76a;border-radius:16px;padding:16px;background:#f6fef9">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
                                <div>
                                    <strong>{{ $record->browser }} on {{ $record->os }}</strong>
                                    @if($device['is_current'])<span style="margin-left:8px;font-size:12px;color:#027a48">Current device</span>@endif
                                    <p style="margin:6px 0 0;font-size:13px;color:#667085">IP: {{ $record->ip_address ?? 'Unknown' }}</p>
                                    <p style="margin:4px 0 0;font-size:12px;color:#667085">Last used {{ $record->last_used_at?->diffForHumans() ?? 'Unknown' }}</p>
                                    <p style="margin:4px 0 0;font-size:12px;color:#027a48">Trusted</p>
                                </div>
                                <form method="POST" action="{{ route('account.devices.untrust', $record) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="padding:8px 12px;border:1px solid #d0d5dd;border-radius:10px;background:#fff;cursor:pointer">Remove trust</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section style="margin-top:28px">
            <h2 style="font-size:18px;margin-bottom:8px">Known devices</h2>
            <p style="font-size:13px;color:#667085;margin-bottom:12px">Browsers and systems that have signed in to your account.</p>

            @if($devices->isEmpty())
                <p class="aa-muted">No devices recorded yet.</p>
            @else
                <div style="display:grid;gap:12px">
                    @foreach($devices as $device)
                        @php($record = $device['model'])
                        <div style="border:1px solid #e4e7ec;border-radius:16px;padding:16px">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
                                <div>
                                    <strong>{{ $record->browser }} on {{ $record->os }}</strong>
                                    @if($device['is_current'])<span style="margin-left:8px;font-size:12px;color:#027a48">Current device</span>@endif
                                    @if($device['is_active'])<span style="margin-left:8px;font-size:12px;color:#175cd3">Active session</span>@endif
                                    <p style="margin:6px 0 0;font-size:13px;color:#667085">IP: {{ $record->ip_address ?? 'Unknown' }}</p>
                                    <p style="margin:4px 0 0;font-size:12px;color:#667085">Last used {{ $record->last_used_at?->diffForHumans() ?? 'Unknown' }}</p>
                                    @if($record->is_trusted)
                                        <p style="margin:4px 0 0;font-size:12px;color:#027a48">Trusted</p>
                                    @endif
                                </div>
                                @if(! $record->is_trusted)
                                    <form method="POST" action="{{ route('account.devices.trust', $record) }}">
                                        @csrf
                                        <button type="submit" style="padding:8px 12px;border:1px solid #12b76a;border-radius:10px;background:#fff;color:#027a48;cursor:pointer">Trust device</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section style="margin-top:28px">
            <h2 style="font-size:18px;margin-bottom:8px">Active sessions</h2>
            <p style="font-size:13px;color:#667085;margin-bottom:12px">Sign out from other browsers or devices remotely.</p>

            @if($sessions->isEmpty())
                <p class="aa-muted">No active sessions found.</p>
            @else
                <div style="display:grid;gap:12px">
                    @foreach($sessions as $session)
                        <div style="border:1px solid #e4e7ec;border-radius:16px;padding:16px">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
                                <div>
                                    <strong>{{ $session['is_current'] ? 'Current session' : 'Other session' }}</strong>
                                    <p style="margin:6px 0 0;font-size:13px;color:#667085">{{ $session['browser'] }} on {{ $session['os'] }}</p>
                                    <p style="margin:4px 0 0;font-size:13px;color:#667085">IP: {{ $session['ip_address'] ?? 'Unknown' }}</p>
                                    <p style="margin:4px 0 0;font-size:12px;color:#667085">Last active {{ $session['last_activity']->diffForHumans() }}</p>
                                </div>
                                @if(! $session['is_current'])
                                    <form method="POST" action="{{ route('account.devices.sessions.destroy', $session['id']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="padding:8px 12px;border:1px solid #fda29b;border-radius:10px;background:#fff;color:#b42318;cursor:pointer">Log out</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($sessions->where('is_current', false)->isNotEmpty())
                <form method="POST" action="{{ route('account.devices.sessions.destroy-others') }}" style="margin-top:20px">
                    @csrf
                    @method('DELETE')
                    <button class="btn-orange" type="submit" style="width:100%;justify-content:center">Log out all other devices</button>
                </form>
            @endif
        </section>

        <p style="margin-top:20px;text-align:center"><a class="auth-link" href="{{ auth()->user()->dashboardPath() }}">Back to dashboard</a></p>
    </div>
</div>
</x-layouts.guest>
