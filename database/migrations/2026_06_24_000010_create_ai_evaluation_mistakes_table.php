<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_evaluation_mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_evaluation_score_id')->constrained()->cascadeOnDelete();
            $table->string('category', 50);
            $table->string('severity', 20)->default('info');
            $table->text('message');
            $table->text('suggestion')->nullable();
            $table->text('reference_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['ai_evaluation_score_id', 'category']);
            $table->index(['ai_evaluation_score_id', 'sort_order']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluation_mistakes');
    }
};
