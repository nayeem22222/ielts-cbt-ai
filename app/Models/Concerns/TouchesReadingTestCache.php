<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ReadingTest;

trait TouchesReadingTestCache
{
    protected static function bootTouchesReadingTestCache(): void
    {
        static::saved(function (self $model): void {
            $model->touchReadingTestForCache();
        });

        static::deleted(function (self $model): void {
            $model->touchReadingTestForCache();
        });
    }

    abstract protected function touchReadingTestForCache(): void;

    protected function touchReadingTestById(?int $readingTestId): void
    {
        if ($readingTestId === null) {
            return;
        }

        ReadingTest::query()->whereKey($readingTestId)->update(['updated_at' => now()]);
    }
}
