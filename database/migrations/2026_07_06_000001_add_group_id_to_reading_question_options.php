<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_question_options', function (Blueprint $table): void {
            if (! Schema::hasColumn('reading_question_options', 'group_id')) {
                $table->foreignId('group_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('reading_question_groups')
                    ->cascadeOnDelete();

                $table->index(['group_id', 'option_key']);
                $table->index(['group_id', 'sort_order']);
            }
        });

        Schema::table('reading_question_options', function (Blueprint $table): void {
            $table->foreignId('question_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reading_question_options', function (Blueprint $table): void {
            if (Schema::hasColumn('reading_question_options', 'group_id')) {
                $table->dropConstrainedForeignId('group_id');
            }
        });

        Schema::table('reading_question_options', function (Blueprint $table): void {
            $table->foreignId('question_id')->nullable(false)->change();
        });
    }
};
