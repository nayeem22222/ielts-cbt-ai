<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reading_attempts')) {
            Schema::table('reading_attempts', function (Blueprint $table): void {
                if (! $this->indexExists('reading_attempts', 'reading_attempts_started_at_index')) {
                    $table->index('started_at');
                }
            });
        }

        if (Schema::hasTable('reading_tests')) {
            Schema::table('reading_tests', function (Blueprint $table): void {
                if (! $this->indexExists('reading_tests', 'reading_tests_status_index')) {
                    $table->index('status');
                }
                if (! $this->indexExists('reading_tests', 'reading_tests_exam_type_index')) {
                    $table->index('exam_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reading_attempts')) {
            Schema::table('reading_attempts', function (Blueprint $table): void {
                if ($this->indexExists('reading_attempts', 'reading_attempts_started_at_index')) {
                    $table->dropIndex(['started_at']);
                }
            });
        }

        if (Schema::hasTable('reading_tests')) {
            Schema::table('reading_tests', function (Blueprint $table): void {
                if ($this->indexExists('reading_tests', 'reading_tests_status_index')) {
                    $table->dropIndex(['status']);
                }
                if ($this->indexExists('reading_tests', 'reading_tests_exam_type_index')) {
                    $table->dropIndex(['exam_type']);
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName],
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
