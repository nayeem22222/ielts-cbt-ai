<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Auth\AuthenticationEventType;
use App\Models\AuthEventLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        return [
            'kpis' => $this->kpis(),
            'charts' => $this->charts(),
            'recentActivities' => $this->recentActivities(),
            'quickActions' => $this->quickActions(),
            'notifications' => $this->notifications(),
            'serverHealth' => $this->serverHealth(),
            'aiQueue' => $this->aiQueue(),
        ];
    }

    /**
     * @return list<array{label: string, value: string, change: ?string, tone: string, icon: string}>
     */
    private function kpis(): array
    {
        $users = User::query()->count();
        $usersThisMonth = User::query()->where('created_at', '>=', now()->startOfMonth())->count();
        $usersLastMonth = User::query()
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->count();

        $revenue = $this->sumPaidOrders();
        $orders = $this->countOrders();
        $aiEvaluations = $this->countAiRequests('completed');

        return [
            [
                'label' => 'Total Users',
                'value' => number_format($users),
                'change' => $this->percentChange($usersThisMonth, $usersLastMonth),
                'tone' => 'blue',
                'icon' => '👥',
            ],
            [
                'label' => 'Revenue',
                'value' => $this->formatMoney($revenue),
                'change' => $orders > 0 ? number_format($orders).' orders' : null,
                'tone' => 'green',
                'icon' => '💰',
            ],
            [
                'label' => 'AI Evaluations',
                'value' => number_format($aiEvaluations),
                'change' => $this->countAiRequests('pending').' pending',
                'tone' => 'purple',
                'icon' => '🤖',
            ],
            [
                'label' => 'Active Sessions',
                'value' => number_format($this->countActiveSessions()),
                'change' => null,
                'tone' => 'amber',
                'icon' => '📡',
            ],
        ];
    }

    /**
     * @return array<string, list<array{label: string, value: int}>>
     */
    private function charts(): array
    {
        return [
            'userGrowth' => $this->monthlySeries('users', 'created_at'),
            'revenue' => $this->monthlyRevenueSeries(),
            'completionRate' => [
                ['label' => 'Reading', 'value' => 82],
                ['label' => 'Listening', 'value' => 76],
                ['label' => 'Writing', 'value' => 68],
                ['label' => 'Speaking', 'value' => 71],
            ],
        ];
    }

    /**
     * @return list<array{title: string, description: string, time: string, tone: string}>
     */
    private function recentActivities(): array
    {
        $activities = collect();

        if (Schema::hasTable('auth_event_logs')) {
            AuthEventLog::query()
                ->with('user')
                ->latest('created_at')
                ->limit(6)
                ->get()
                ->each(function (AuthEventLog $log) use ($activities): void {
                    $activities->push([
                        'title' => $this->activityTitle($log->event),
                        'description' => $log->user?->name ?? $log->email ?? 'System',
                        'time' => $log->created_at?->diffForHumans() ?? 'Just now',
                        'tone' => $this->activityTone($log->event),
                    ]);
                });
        }

        if ($activities->isEmpty()) {
            User::query()->latest('id')->limit(5)->get()->each(function (User $user) use ($activities): void {
                $activities->push([
                    'title' => 'New user registered',
                    'description' => $user->name,
                    'time' => $user->created_at?->diffForHumans() ?? 'Recently',
                    'tone' => 'blue',
                ]);
            });
        }

        return $activities->take(8)->values()->all();
    }

    /**
     * @return list<array{label: string, href: string, icon: string, description: string}>
     */
    private function quickActions(): array
    {
        return [
            [
                'label' => 'Add User',
                'href' => route('admin.users.create'),
                'icon' => '➕',
                'description' => 'Provision student, teacher, or admin',
            ],
            [
                'label' => 'Manage Roles',
                'href' => route('admin.roles.index'),
                'icon' => '🛡️',
                'description' => 'Review RBAC assignments',
            ],
            [
                'label' => 'Permissions',
                'href' => route('admin.permissions.index'),
                'icon' => '🔐',
                'description' => 'Audit capability registry',
            ],
            [
                'label' => 'Device Security',
                'href' => route('account.devices.index'),
                'icon' => '💻',
                'description' => 'Sessions and trusted devices',
            ],
        ];
    }

    /**
     * @return list<array{title: string, body: string, time: string, unread: bool}>
     */
    private function notifications(): array
    {
        if (! Schema::hasTable('auth_event_logs')) {
            return [];
        }

        return AuthEventLog::query()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (AuthEventLog $log): array => [
                'title' => $this->notificationTitle($log->event),
                'body' => ($log->user?->email ?? $log->email ?? 'Unknown account').' · '.$log->ip_address,
                'time' => $log->created_at?->diffForHumans() ?? 'Just now',
                'unread' => $log->created_at?->greaterThan(now()->subHours(24)) ?? false,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, status: string, detail: string}>
     */
    private function serverHealth(): array
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks[] = ['label' => 'Database', 'status' => 'healthy', 'detail' => 'Connected'];
        } catch (\Throwable) {
            $checks[] = ['label' => 'Database', 'status' => 'down', 'detail' => 'Connection failed'];
        }

        try {
            Cache::put('dashboard_health_check', 'ok', 10);
            $cacheOk = Cache::get('dashboard_health_check') === 'ok';
            $checks[] = [
                'label' => 'Cache',
                'status' => $cacheOk ? 'healthy' : 'degraded',
                'detail' => $cacheOk ? 'Read/write OK' : 'Read/write issue',
            ];
        } catch (\Throwable) {
            $checks[] = ['label' => 'Cache', 'status' => 'down', 'detail' => 'Unavailable'];
        }

        $checks[] = [
            'label' => 'Queue',
            'status' => config('queue.default') === 'sync' ? 'degraded' : 'healthy',
            'detail' => strtoupper((string) config('queue.default')).' driver',
        ];

        $checks[] = [
            'label' => 'Application',
            'status' => 'healthy',
            'detail' => 'Laravel '.app()->version(),
        ];

        return $checks;
    }

    /**
     * @return array{summary: array<string, int>, recent: list<array{id: int, status: string, time: string}>}
     */
    private function aiQueue(): array
    {
        if (! Schema::hasTable('ai_requests')) {
            return [
                'summary' => [
                    'pending' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'failed' => 0,
                ],
                'recent' => [],
            ];
        }

        $summary = [
            'pending' => (int) DB::table('ai_requests')->where('status', 'pending')->count(),
            'processing' => (int) DB::table('ai_requests')->where('status', 'processing')->count(),
            'completed' => (int) DB::table('ai_requests')->where('status', 'completed')->count(),
            'failed' => (int) DB::table('ai_requests')->whereIn('status', ['failed', 'error'])->count(),
        ];

        $recent = DB::table('ai_requests')
            ->select(['id', 'status', 'created_at'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'status' => (string) $row->status,
                'time' => Carbon::parse($row->created_at)->diffForHumans(),
            ])
            ->all();

        return [
            'summary' => $summary,
            'recent' => $recent,
        ];
    }

    private function sumPaidOrders(): float
    {
        if (! Schema::hasTable('orders')) {
            return 0.0;
        }

        return (float) DB::table('orders')
            ->whereNotNull('paid_at')
            ->sum('total');
    }

    private function countOrders(): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        return (int) DB::table('orders')->count();
    }

    private function countAiRequests(string $status): int
    {
        if (! Schema::hasTable('ai_requests')) {
            return 0;
        }

        return (int) DB::table('ai_requests')->where('status', $status)->count();
    }

    private function countActiveSessions(): int
    {
        if (! Schema::hasTable('sessions')) {
            return 0;
        }

        return (int) DB::table('sessions')
            ->whereNotNull('user_id')
            ->count();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function monthlySeries(string $table, string $column): array
    {
        if (! Schema::hasTable($table)) {
            return $this->emptyMonthlySeries();
        }

        $start = now()->subMonths(5)->startOfMonth();
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::table($table)
                ->selectRaw("strftime('%Y-%m', {$column}) as month, COUNT(*) as total")
                ->where($column, '>=', $start)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            $rows = DB::table($table)
                ->selectRaw("DATE_FORMAT({$column}, '%Y-%m') as month, COUNT(*) as total")
                ->where($column, '>=', $start)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        $indexed = $rows->keyBy('month');

        return collect(range(0, 5))
            ->map(function (int $offset) use ($indexed): array {
                $month = now()->subMonths(5 - $offset)->format('Y-m');

                return [
                    'label' => now()->subMonths(5 - $offset)->format('M'),
                    'value' => (int) ($indexed->get($month)?->total ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function monthlyRevenueSeries(): array
    {
        if (! Schema::hasTable('orders')) {
            return $this->emptyMonthlySeries();
        }

        $start = now()->subMonths(5)->startOfMonth();
        $driver = DB::getDriverName();

        $rows = DB::table('orders')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start);

        if ($driver === 'sqlite') {
            $rows = $rows
                ->selectRaw("strftime('%Y-%m', paid_at) as month, SUM(total) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            $rows = $rows
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(total) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        $indexed = $rows->keyBy('month');

        return collect(range(0, 5))
            ->map(function (int $offset) use ($indexed): array {
                $month = now()->subMonths(5 - $offset)->format('Y-m');

                return [
                    'label' => now()->subMonths(5 - $offset)->format('M'),
                    'value' => (int) round((float) ($indexed->get($month)?->total ?? 0)),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function emptyMonthlySeries(): array
    {
        return collect(range(0, 5))
            ->map(fn (int $offset): array => [
                'label' => now()->subMonths(5 - $offset)->format('M'),
                'value' => 0,
            ])
            ->all();
    }

    private function formatMoney(float $amount): string
    {
        if ($amount >= 1_000_000) {
            return '৳'.number_format($amount / 1_000_000, 1).'M';
        }

        if ($amount >= 1_000) {
            return '৳'.number_format($amount / 1_000, 1).'K';
        }

        return '৳'.number_format($amount, 0);
    }

    private function percentChange(int $current, int $previous): ?string
    {
        if ($previous === 0) {
            return $current > 0 ? '+'.$current.' this month' : null;
        }

        $change = round((($current - $previous) / $previous) * 100);

        return ($change >= 0 ? '+' : '').$change.'% vs last month';
    }

    private function activityTitle(string $event): string
    {
        return match ($event) {
            AuthenticationEventType::UserRegistered->value => 'User registered',
            AuthenticationEventType::UserLoggedIn->value => 'User logged in',
            AuthenticationEventType::UserLoggedOut->value => 'User logged out',
            AuthenticationEventType::PasswordChanged->value => 'Password changed',
            AuthenticationEventType::LoginFailed->value => 'Failed login attempt',
            default => ucwords(str_replace('_', ' ', $event)),
        };
    }

    private function activityTone(string $event): string
    {
        return match ($event) {
            AuthenticationEventType::LoginFailed->value => 'red',
            AuthenticationEventType::PasswordChanged->value => 'amber',
            AuthenticationEventType::UserRegistered->value => 'green',
            default => 'blue',
        };
    }

    private function notificationTitle(string $event): string
    {
        return match ($event) {
            AuthenticationEventType::LoginFailed->value => 'Security alert',
            AuthenticationEventType::PasswordChanged->value => 'Password update',
            AuthenticationEventType::UserRegistered->value => 'New registration',
            default => 'Authentication event',
        };
    }
}
