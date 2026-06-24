<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reading_answers')) {
            Schema::table('reading_answers', function (Blueprint $table): void {
                if (! Schema::hasColumn('reading_answers', 'marks_awarded')) {
                    $table->decimal('marks_awarded', 5, 2)->nullable()->after('is_correct');
                }

                if (! Schema::hasColumn('reading_answers', 'evaluated_at')) {
                    $table->timestamp('evaluated_at')->nullable()->after('marks_awarded');
                }

                if (! Schema::hasColumn('reading_answers', 'evaluation_json')) {
                    $table->json('evaluation_json')->nullable()->after('evaluated_at');
                }
            });
        }

        if (Schema::hasTable('reading_attempts')) {
            Schema::table('reading_attempts', function (Blueprint $table): void {
                if (! Schema::hasColumn('reading_attempts', 'evaluated_at')) {
                    $table->timestamp('evaluated_at')->nullable()->after('submitted_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reading_answers')) {
            Schema::table('reading_answers', function (Blueprint $table): void {
                if (Schema::hasColumn('reading_answers', 'evaluation_json')) {
                    $table->dropColumn('evaluation_json');
                }

                if (Schema::hasColumn('reading_answers', 'evaluated_at')) {
                    $table->dropColumn('evaluated_at');
                }

                if (Schema::hasColumn('reading_answers', 'marks_awarded')) {
                    $table->dropColumn('marks_awarded');
                }
            });
        }

        if (Schema::hasTable('reading_attempts')) {
            Schema::table('reading_attempts', function (Blueprint $table): void {
                if (Schema::hasColumn('reading_attempts', 'evaluated_at')) {
                    $table->dropColumn('evaluated_at');
                }
            });
        }
    }
};
