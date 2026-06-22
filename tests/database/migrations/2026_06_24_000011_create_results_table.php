<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->decimal('overall_band', 2, 1)->nullable();
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('computed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('test_attempt_id');
            $table->index(['overall_band', 'computed_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
