<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_question_groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('reading_question_groups', 'status')) {
                $table->string('status', 20)->default('draft')->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reading_question_groups', function (Blueprint $table): void {
            if (Schema::hasColumn('reading_question_groups', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
