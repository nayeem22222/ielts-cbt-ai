<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_uuid');
            $table->string('device_name')->nullable();
            $table->string('platform', 30);
            $table->string('push_token')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_uuid']);
            $table->index(['user_id', 'last_used_at']);
            $table->index('platform');
            $table->index('is_trusted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
