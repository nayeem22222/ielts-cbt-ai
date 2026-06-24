<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_passages', function (Blueprint $table): void {
            if (! Schema::hasColumn('reading_passages', 'start_question')) {
                $table->unsignedSmallInteger('start_question')->nullable()->after('instruction');
            }

            if (! Schema::hasColumn('reading_passages', 'end_question')) {
                $table->unsignedSmallInteger('end_question')->nullable()->after('start_question');
            }

            if (! Schema::hasColumn('reading_passages', 'status')) {
                $table->string('status', 20)->default('draft')->after('content_text');
            }

            if (! Schema::hasColumn('reading_passages', 'settings')) {
                $table->json('settings')->nullable()->after('status');
            }
        });

        Schema::table('reading_passages', function (Blueprint $table): void {
            if (Schema::hasColumn('reading_passages', 'start_question')) {
                $table->index(['reading_test_id', 'start_question', 'end_question'], 'reading_passages_test_question_range_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reading_passages', function (Blueprint $table): void {
            if (Schema::hasColumn('reading_passages', 'start_question')) {
                $table->dropIndex('reading_passages_test_question_range_idx');
            }

            $columns = ['settings', 'status', 'end_question', 'start_question'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('reading_passages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
