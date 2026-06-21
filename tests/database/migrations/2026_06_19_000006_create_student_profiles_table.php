<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('target_band', 2, 1)->nullable();
            $table->string('exam_type', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->text('bio')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->index('exam_type');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
