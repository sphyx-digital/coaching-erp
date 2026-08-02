<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\Question;
use App\Services\Audit\AuditLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Authoring side of the exam engine: assembling questions onto an exam,
 * keeping the cached total in step, and publishing / closing.
 */
class ExamService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Attach questions to an exam (idempotent), appending in the given order.
     *
     * @param  array<int>  $questionIds
     */
    public function addQuestions(Exam $exam, array $questionIds): Exam
    {
        return DB::transaction(function () use ($exam, $questionIds) {
            $start = (int) $exam->questions()->max('exam_question.sequence');
            $seq = $start;
            foreach ($questionIds as $id) {
                if ($exam->questions()->where('questions.id', $id)->exists()) {
                    continue;
                }
                $exam->questions()->attach($id, ['sequence' => ++$seq]);
            }

            return $this->syncTotal($exam->fresh('questions'));
        });
    }

    public function removeQuestion(Exam $exam, int $questionId): Exam
    {
        $exam->questions()->detach($questionId);

        return $this->syncTotal($exam->fresh('questions'));
    }

    /** Recompute and persist the exam's total marks from its questions. */
    public function syncTotal(Exam $exam): Exam
    {
        $total = $exam->questions->sum(fn (Question $q) => $exam->marksFor($q));
        $exam->forceFill(['total_marks' => $total])->save();

        return $exam;
    }

    public function publish(Exam $exam): Exam
    {
        if ($exam->questions()->count() === 0) {
            throw new DomainException('Cannot publish an exam with no questions.');
        }

        $this->syncTotal($exam->fresh('questions'));
        $exam->forceFill(['status' => 'published', 'published_at' => now()])->save();
        $this->audit->log('exam.published', $exam, after: ['title' => $exam->title, 'total_marks' => $exam->total_marks]);

        return $exam;
    }

    public function close(Exam $exam): Exam
    {
        $exam->forceFill(['status' => 'closed'])->save();
        $this->audit->log('exam.closed', $exam);

        return $exam;
    }
}
