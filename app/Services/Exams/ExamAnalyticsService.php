<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\ExamAttemptAnswer;

/**
 * Read-only analytics over graded attempts: exam-level performance and
 * per-question difficulty.
 */
class ExamAnalyticsService
{
    /**
     * @return array{attempts:int,average:float,highest:int,lowest:int,pass_count:int,pass_rate:float,max_score:int}
     */
    public function summary(Exam $exam): array
    {
        $graded = $exam->attempts()->whereIn('status', ['submitted', 'auto_submitted'])->get();
        $count = $graded->count();

        if ($count === 0) {
            return ['attempts' => 0, 'average' => 0.0, 'highest' => 0, 'lowest' => 0, 'pass_count' => 0, 'pass_rate' => 0.0, 'max_score' => (int) $exam->total_marks];
        }

        $passed = $graded->filter(fn ($a) => $a->passed())->count();

        return [
            'attempts' => $count,
            'average' => round($graded->avg('score'), 2),
            'highest' => (int) $graded->max('score'),
            'lowest' => (int) $graded->min('score'),
            'pass_count' => $passed,
            'pass_rate' => round($passed / $count * 100, 2),
            'max_score' => (int) $exam->total_marks,
        ];
    }

    /**
     * Per-question difficulty: how many attempts answered it and what share
     * got it right. Lower correct-share = harder.
     *
     * @return array<int,array{question_id:int,answered:int,correct:int,correct_rate:float}>
     */
    public function questionStats(Exam $exam): array
    {
        $attemptIds = $exam->attempts()->whereIn('status', ['submitted', 'auto_submitted'])->pluck('id');

        if ($attemptIds->isEmpty()) {
            return [];
        }

        $rows = ExamAttemptAnswer::whereIn('exam_attempt_id', $attemptIds)
            ->selectRaw('question_id,
                COUNT(*) as total,
                SUM(CASE WHEN selected_option IS NOT NULL THEN 1 ELSE 0 END) as answered,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->groupBy('question_id')
            ->get();

        return $rows->map(fn ($r) => [
            'question_id' => (int) $r->question_id,
            'answered' => (int) $r->answered,
            'correct' => (int) $r->correct,
            'correct_rate' => $r->answered > 0 ? round($r->correct / $r->answered * 100, 1) : 0.0,
        ])->all();
    }
}
