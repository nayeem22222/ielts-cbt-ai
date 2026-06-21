<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->string('guard_name', 50)->default('web');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
            $table->index('group');
            $table->index('guard_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
