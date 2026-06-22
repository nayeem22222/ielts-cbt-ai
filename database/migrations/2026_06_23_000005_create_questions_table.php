<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->string('type', 50);
            $table->unsignedSmallInteger('question_number')->nullable();
            $table->longText('prompt');
            $table->json('stimulus')->nullable();
            $table->string('difficulty', 20)->default('medium');
            $table->decimal('marks', 5, 2)->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['question_bank_id', 'sort_order']);
            $table->index(['module', 'type', 'status']);
            $table->index('difficulty');
            $table->index('deleted_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
