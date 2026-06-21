<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->string('access_level', 30)->default('standard');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->index('access_level');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
    }
};
