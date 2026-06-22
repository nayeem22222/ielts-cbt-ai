<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_attempt_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_package_id')->constrained('student_packages')->cascadeOnDelete();
            $table->string('module', 20);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'student_package_id', 'module']);
            $table->index(['user_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_attempt_usages');
    }
};
