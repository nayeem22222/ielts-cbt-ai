<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_attempts')) {
            return;
        }

        Schema::table('listening_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('listening_attempts', 'listening_started_at')) {
                $table->timestamp('listening_started_at')->nullable()->after('started_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'listening_ended_at')) {
                $table->timestamp('listening_ended_at')->nullable()->after('listening_started_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'transfer_started_at')) {
                $table->timestamp('transfer_started_at')->nullable()->after('listening_ended_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'transfer_ended_at')) {
                $table->timestamp('transfer_ended_at')->nullable()->after('transfer_started_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'current_phase')) {
                $table->string('current_phase', 20)->nullable()->index()->after('status');
            }

            if (! Schema::hasColumn('listening_attempts', 'timer_started_at')) {
                $table->timestamp('timer_started_at')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'last_timer_sync_at')) {
                $table->timestamp('last_timer_sync_at')->nullable()->after('timer_started_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'auto_submitted_at')) {
                $table->timestamp('auto_submitted_at')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'timer_meta')) {
                $table->json('timer_meta')->nullable()->after('result_meta');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('listening_attempts')) {
            return;
        }

        Schema::table('listening_attempts', function (Blueprint $table): void {
            $columns = [
                'listening_started_at',
                'listening_ended_at',
                'transfer_started_at',
                'transfer_ended_at',
                'current_phase',
                'timer_started_at',
                'last_timer_sync_at',
                'auto_submitted_at',
                'timer_meta',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('listening_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
