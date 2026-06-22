<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->decimal('band', 2, 1)->nullable();
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->unsignedSmallInteger('total_count')->nullable();
            $table->string('scoring_method', 20)->default('auto');
            $table->timestamps();

            $table->unique(['result_id', 'module']);
            $table->index(['module', 'band']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_scores');
    }
};
