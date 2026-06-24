<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_notes', function (Blueprint $table): void {
            $table->text('selected_text')->nullable()->after('content');
            $table->unsignedInteger('start_offset')->nullable()->after('selected_text');
            $table->unsignedInteger('end_offset')->nullable()->after('start_offset');
        });
    }

    public function down(): void
    {
        Schema::table('reading_notes', function (Blueprint $table): void {
            $table->dropColumn(['selected_text', 'start_offset', 'end_offset']);
        });
    }
};
