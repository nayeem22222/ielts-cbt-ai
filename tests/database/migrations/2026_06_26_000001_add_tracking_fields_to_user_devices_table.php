<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->string('browser', 100)->nullable()->after('device_name');
            $table->string('os', 100)->nullable()->after('browser');
            $table->text('user_agent')->nullable()->after('os');
            $table->string('session_id')->nullable()->after('user_agent');

            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn(['browser', 'os', 'user_agent', 'session_id']);
        });
    }
};
