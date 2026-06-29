<?php

declare(strict_types=1);

namespace App\Console\Commands\Listening;

use App\Services\Listening\Student\ListeningAutoSubmitService;
use Illuminate\Console\Command;

class AutoSubmitExpiredListeningAttemptsCommand extends Command
{
    protected $signature = 'listening:attempts:auto-submit-expired {--limit=100 : Max attempts to process}';

    protected $description = 'Auto-submit in-progress listening attempts whose official timer has expired.';

    public function handle(ListeningAutoSubmitService $service): int
    {
        $limit = (int) ($this->option('limit') ?? 100);
        $count = $service->bulkAutoSubmitExpired($limit);

        $this->info("Auto-submitted {$count} expired listening attempt(s).");

        return self::SUCCESS;
    }
}
