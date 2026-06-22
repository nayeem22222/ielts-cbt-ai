<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autosave_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->timestamp('saved_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['test_attempt_id', 'saved_at']);
            $table->index(['student_answer_id', 'saved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autosave_logs');
    }
};
