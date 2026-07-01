<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listening_attempts', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        DB::table('listening_attempts')
            ->whereNull('uuid')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $attempt): void {
                DB::table('listening_attempts')
                    ->where('id', $attempt->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        Schema::table('listening_attempts', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('listening_attempts', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
