<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id', 50)->nullable();
            $table->string('department')->nullable();
            $table->text('qualifications')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->index('employee_id');
            $table->index('is_verified');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
