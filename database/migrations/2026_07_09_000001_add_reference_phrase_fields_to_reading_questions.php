<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('reading_questions', 'reference_type')) {
                $table->string('reference_type', 20)->nullable()->after('reference_paragraph');
            }

            if (! Schema::hasColumn('reading_questions', 'reference_phrase')) {
                $table->text('reference_phrase')->nullable()->after('reference_type');
            }

            if (! Schema::hasColumn('reading_questions', 'reference_sentence')) {
                $table->text('reference_sentence')->nullable()->after('reference_phrase');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reading_questions', function (Blueprint $table) {
            $columns = ['reference_type', 'reference_phrase', 'reference_sentence'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('reading_questions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
