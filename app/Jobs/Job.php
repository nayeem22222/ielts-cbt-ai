<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

abstract class Job implements ShouldQueue
{
    use Queueable;
}
