<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listening_attempt_answer_evaluations')) {
            $this->repairExistingTable();

            return;
        }

        Schema::create('listening_attempt_answer_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('listening_attempt_evaluation_id');
            $table->unsignedBigInteger('listening_attempt_answer_id');
            $table->unsignedBigInteger('listening_attempt_id');
            $table->unsignedBigInteger('listening_question_id');
            $table->unsignedTinyInteger('question_number')->index();
            $table->string('question_type', 50)->index();
            $table->json('student_answer_snapshot')->nullable();
            $table->json('normalized_student_answer')->nullable();
            $table->json('correct_answer_snapshot')->nullable();
            $table->json('accepted_answers_snapshot')->nullable();
            $table->json('matched_answer')->nullable();
            $table->boolean('is_correct')->default(false)->index();
            $table->decimal('marks_available', 5, 2)->default(1);
            $table->decimal('marks_awarded', 5, 2)->default(0);
            $table->string('match_status', 30)->default('incorrect')->index();
            $table->string('match_reason', 60)->nullable();
            $table->json('normalization_steps')->nullable();
            $table->json('evaluator_meta')->nullable();
            $table->timestamps();

            $table->foreign('listening_attempt_evaluation_id', 'laa_eval_fk')
                ->references('id')->on('listening_attempt_evaluations')->cascadeOnDelete();
            $table->foreign('listening_attempt_answer_id', 'laa_answer_fk')
                ->references('id')->on('listening_attempt_answers')->cascadeOnDelete();
            $table->foreign('listening_attempt_id', 'laa_attempt_fk')
                ->references('id')->on('listening_attempts')->cascadeOnDelete();
            $table->foreign('listening_question_id', 'laa_question_fk')
                ->references('id')->on('listening_questions')->restrictOnDelete();

            $table->index('listening_attempt_evaluation_id', 'laa_eval_eval_id_idx');
            $table->index('listening_attempt_id', 'laa_eval_attempt_id_idx');
            $table->index('listening_question_id', 'laa_eval_question_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_attempt_answer_evaluations');
    }

    private function repairExistingTable(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->foreignKeys() as $sql) {
            try {
                DB::statement($sql);
            } catch (Throwable) {
                // The table may already be fully repaired; keep the migration idempotent.
            }
        }
    }

    /**
     * @return list<string>
     */
    private function foreignKeys(): array
    {
        return [
            'ALTER TABLE listening_attempt_answer_evaluations ADD CONSTRAINT laa_eval_fk FOREIGN KEY (listening_attempt_evaluation_id) REFERENCES listening_attempt_evaluations (id) ON DELETE CASCADE',
            'ALTER TABLE listening_attempt_answer_evaluations ADD CONSTRAINT laa_answer_fk FOREIGN KEY (listening_attempt_answer_id) REFERENCES listening_attempt_answers (id) ON DELETE CASCADE',
            'ALTER TABLE listening_attempt_answer_evaluations ADD CONSTRAINT laa_attempt_fk FOREIGN KEY (listening_attempt_id) REFERENCES listening_attempts (id) ON DELETE CASCADE',
            'ALTER TABLE listening_attempt_answer_evaluations ADD CONSTRAINT laa_question_fk FOREIGN KEY (listening_question_id) REFERENCES listening_questions (id) ON DELETE RESTRICT',
        ];
    }
};
