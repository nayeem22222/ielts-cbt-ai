<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Course\PublishStatus;
use App\Models\ReadingTest;
use Illuminate\Support\Facades\Cache;

class ReadingTestPublicCacheService
{
    public function cacheKey(ReadingTest $test): string
    {
        $version = $test->updated_at?->getTimestamp() ?? $test->id;

        return "reading_test_public:{$test->id}:v{$version}";
    }

    /**
     * @param  callable(ReadingTest): ReadingTest  $loader
     */
    public function remember(ReadingTest $test, callable $loader): ReadingTest
    {
        if ($test->status !== PublishStatus::Published) {
            return $loader($test);
        }

        /** @var ReadingTest $cached */
        $cached = Cache::remember(
            $this->cacheKey($test),
            now()->addDay(),
            fn (): ReadingTest => $loader($test->fresh() ?? $test),
        );

        return $cached;
    }

    public function forget(ReadingTest $test): void
    {
        Cache::forget($this->cacheKey($test));
    }
}
