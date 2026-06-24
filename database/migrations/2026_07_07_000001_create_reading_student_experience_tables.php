<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('reading_attempts')->cascadeOnDelete();
            $table->foreignId('passage_id')->constrained('reading_passages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('selected_text');
            $table->unsignedInteger('start_offset');
            $table->unsignedInteger('end_offset');
            $table->string('highlight_color', 20)->default('yellow');
            $table->text('note_text')->nullable();
            $table->timestamps();

            $table->index(['attempt_id', 'passage_id']);
            $table->index(['user_id', 'attempt_id']);
        });

        Schema::create('reading_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('reading_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('reading_questions')->nullOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained('reading_passages')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('content');
            $table->timestamps();

            $table->index(['attempt_id', 'user_id']);
        });

        Schema::create('reading_question_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reading_test_id')->constrained('reading_tests')->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained('reading_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('reading_questions')->cascadeOnDelete();
            $table->unsignedSmallInteger('question_number');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('issue_type', 40);
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->text('admin_reply')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['reading_test_id', 'question_id']);
        });

        Schema::table('reading_questions', function (Blueprint $table): void {
            $table->unsignedInteger('reference_start_offset')->nullable()->after('paragraph_reference');
            $table->unsignedInteger('reference_end_offset')->nullable()->after('reference_start_offset');
            $table->string('reference_paragraph', 30)->nullable()->after('reference_end_offset');
        });
    }

    public function down(): void
    {
        Schema::table('reading_questions', function (Blueprint $table): void {
            $table->dropColumn(['reference_start_offset', 'reference_end_offset', 'reference_paragraph']);
        });

        Schema::dropIfExists('reading_question_tickets');
        Schema::dropIfExists('reading_notes');
        Schema::dropIfExists('reading_highlights');
    }
};
