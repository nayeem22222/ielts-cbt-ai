<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_transcripts')) {
            return;
        }

        Schema::table('listening_transcripts', function (Blueprint $table): void {
            if (! Schema::hasColumn('listening_transcripts', 'passage_title')) {
                $table->string('passage_title')->nullable()->after('title');
            }

            if (! Schema::hasColumn('listening_transcripts', 'passage_note')) {
                $table->longText('passage_note')->nullable()->after('formatted_transcript');
            }

            if (! Schema::hasColumn('listening_transcripts', 'source_type')) {
                $table->string('source_type', 30)->default('manual')->after('is_official');
            }

            if (! Schema::hasColumn('listening_transcripts', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('source_type');
            }

            if (! Schema::hasColumn('listening_transcripts', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('version');
            }

            if (! Schema::hasColumn('listening_transcripts', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('listening_transcripts')) {
            return;
        }

        Schema::table('listening_transcripts', function (Blueprint $table): void {
            if (Schema::hasColumn('listening_transcripts', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }

            foreach (['reviewed_at', 'version', 'source_type', 'passage_note', 'passage_title'] as $column) {
                if (Schema::hasColumn('listening_transcripts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
