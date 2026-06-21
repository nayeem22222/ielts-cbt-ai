<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('status', 20);
            $table->string('failure_reason')->nullable();
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['email', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
