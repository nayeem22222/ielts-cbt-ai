<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listening_tests')) {
            Schema::create('listening_tests', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('test_code')->unique();
                $table->text('description')->nullable();
                $table->string('status', 20)->default('draft');
                $table->string('test_type', 30)->default('academic');
                $table->unsignedTinyInteger('total_sections')->default(4);
                $table->unsignedTinyInteger('total_questions')->default(40);
                $table->unsignedSmallInteger('total_marks')->default(40);
                $table->unsignedSmallInteger('duration_minutes')->default(30);
                $table->unsignedSmallInteger('transfer_time_minutes')->nullable()->default(10);
                $table->boolean('is_active')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->string('difficulty_level', 20)->default('official');
                $table->longText('instructions')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('test_type');
                $table->index('is_active');
                $table->index('created_by');
                $table->index(['status', 'is_active']);
                $table->index('published_at');
                $table->index('deleted_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_tests');
    }
};
