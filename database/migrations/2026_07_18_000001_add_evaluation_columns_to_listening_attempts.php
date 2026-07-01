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
            if (! Schema::hasColumn('listening_attempts', 'evaluated_at')) {
                $table->timestamp('evaluated_at')->nullable()->after('band_score');
            }

            if (! Schema::hasColumn('listening_attempts', 'evaluation_status')) {
                $table->string('evaluation_status', 30)->nullable()->index()->after('evaluated_at');
            }

            if (! Schema::hasColumn('listening_attempts', 'evaluation_version')) {
                $table->string('evaluation_version', 20)->nullable()->after('evaluation_status');
            }

            if (! Schema::hasColumn('listening_attempts', 'evaluation_meta')) {
                $table->json('evaluation_meta')->nullable()->after('evaluation_version');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('listening_attempts')) {
            return;
        }

        Schema::table('listening_attempts', function (Blueprint $table): void {
            $columns = ['evaluated_at', 'evaluation_status', 'evaluation_version', 'evaluation_meta'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('listening_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
