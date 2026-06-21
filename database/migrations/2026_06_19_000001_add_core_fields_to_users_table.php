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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('locale', 10)->default('en')->after('avatar_path');
            $table->string('timezone', 64)->default('UTC')->after('locale');
            $table->string('status', 20)->default('active')->after('timezone');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();

            $table->index('status');
            $table->index('deleted_at');
            $table->index('last_login_at');
        });

        DB::table('users')
            ->whereNull('uuid')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['last_login_at']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'uuid',
                'phone',
                'avatar_path',
                'locale',
                'timezone',
                'status',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
