<?php

namespace App\Services\Assessments;

use App\Models\Assessment;
use App\Models\AssessmentSubject;
use App\Models\GradeScale;
use App\Models\Mark;
use App\Models\ReportCard;
use App\Services\Attendance\AttendanceService;
use App\Services\Audit\AuditLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function __construct(
        private AuditLogger $audit,
        private AttendanceService $attendance,
    ) {}

    /** Grade for a percentage (basis points) from a scale's bands. */
    public function gradeFor(int $percentBp, GradeScale $scale): ?string
    {
        return $scale->bands()
            ->where('min_bp', '<=', $percentBp)
            ->where('max_bp', '>=', $percentBp)
            ->orderByDesc('min_bp')
            ->value('grade');
    }

    /**
     * Enter or update a mark, validated against the subject maximum. A mark
     * above the maximum is rejected. Changes after publish are audited.
     */
    public function enterMark(AssessmentSubject $subject, int $studentId, ?float $marks, bool $absent = false): Mark
    {
        if (! $absent && $marks !== null && $marks > (float) $subject->max_marks) {
            throw new DomainException("Marks {$marks} exceed the maximum of {$subject->max_marks}.");
        }

        $published = $subject->assessment->status === 'published';
        $existing = Mark::where('assessment_subject_id', $subject->id)->where('student_id', $studentId)->first();

        $mark = Mark::updateOrCreate(
            ['assessment_subject_id' => $subject->id, 'student_id' => $studentId],
            ['marks_obtained' => $absent ? null : $marks, 'is_absent' => $absent],
        );

        if ($published) {
            $this->audit->log('mark.changed', $mark,
                before: ['marks' => $existing?->marks_obtained, 'absent' => $existing?->is_absent],
                after: ['marks' => $mark->marks_obtained, 'absent' => $mark->is_absent],
            );
        }

        return $mark;
    }

    /**
     * Compute a student's result for an assessment. A subject with no entered
     * mark is excluded (not treated as zero); an absent mark counts as zero over
     * its maximum.
     *
     * @return array{rows:array, total:float, max_total:float, percent_bp:int, grade:?string}
     */
    public function computeStudent(Assessment $assessment, int $studentId, GradeScale $scale): array
    {
        $rows = [];
        $total = 0.0;
        $maxTotal = 0.0;

        foreach ($assessment->subjects()->with('subject')->get() as $as) {
            $mark = Mark::where('assessment_subject_id', $as->id)->where('student_id', $studentId)->first();

            $entered = $mark && ($mark->marks_obtained !== null || $mark->is_absent);
            if (! $entered) {
                $rows[] = ['subject' => $as->subject?->name, 'marks' => null, 'max' => (float) $as->max_marks, 'grade' => null];

                continue;
            }

            $obtained = $mark->is_absent ? 0.0 : (float) $mark->marks_obtained;
            $max = (float) $as->max_marks;
            $total += $obtained;
            $maxTotal += $max;
            $subPct = $max > 0 ? (int) round($obtained / $max * 10000) : 0;

            $rows[] = ['subject' => $as->subject?->name, 'marks' => $obtained, 'max' => $max, 'grade' => $this->gradeFor($subPct, $scale)];
        }

        $percentBp = $maxTotal > 0 ? (int) round($total / $maxTotal * 10000) : 0;

        return ['rows' => $rows, 'total' => $total, 'max_total' => $maxTotal, 'percent_bp' => $percentBp, 'grade' => $this->gradeFor($percentBp, $scale)];
    }

    /**
     * Generate (publish) a report card. Republishing supersedes the prior
     * version and keeps history. Includes the attendance percentage.
     */
    public function generateReportCard(Assessment $assessment, int $studentId, GradeScale $scale): ReportCard
    {
        $result = $this->computeStudent($assessment, $studentId, $scale);
        $attendanceBp = $this->attendance->studentSummary($studentId, $assessment->batch_id)['percent_bp'];

        return DB::transaction(function () use ($assessment, $studentId, $result, $attendanceBp) {
            ReportCard::where('student_id', $studentId)->where('assessment_id', $assessment->id)
                ->where('status', 'published')->update(['status' => 'superseded']);

            $version = (int) ReportCard::where('student_id', $studentId)->where('assessment_id', $assessment->id)->max('version') + 1;

            $card = ReportCard::create([
                'institute_id' => $assessment->institute_id,
                'student_id' => $studentId,
                'assessment_id' => $assessment->id,
                'academic_session_id' => $assessment->academic_session_id,
                'version' => $version,
                'total_marks' => $result['total'],
                'max_total' => $result['max_total'],
                'percentage_bp' => $result['percent_bp'],
                'overall_grade' => $result['grade'],
                'attendance_bp' => $attendanceBp,
                'payload' => $result['rows'],
                'status' => 'published',
                'published_at' => now(),
            ]);

            $this->audit->log('reportcard.published', $card, after: ['version' => $version, 'percent_bp' => $result['percent_bp']]);

            return $card;
        });
    }

    /**
     * Batch performance: toppers, average percent, and pass count.
     */
    public function batchPerformance(Assessment $assessment, GradeScale $scale, ?int $passBp = null): array
    {
        $passBp ??= (int) client_setting('pass_percent_bp', 4000);
        $studentIds = $assessment->batch->enrollments()->pluck('student_id')->unique();

        $results = [];
        foreach ($studentIds as $sid) {
            $r = $this->computeStudent($assessment, (int) $sid, $scale);
            if ($r['max_total'] > 0) {
                $results[] = ['student_id' => (int) $sid] + $r;
            }
        }

        usort($results, fn ($a, $b) => $b['percent_bp'] <=> $a['percent_bp']);
        $count = count($results);
        $avg = $count ? (int) round(array_sum(array_column($results, 'percent_bp')) / $count) : 0;
        $passed = count(array_filter($results, fn ($r) => $r['percent_bp'] >= $passBp));

        return ['toppers' => array_slice($results, 0, 3), 'average_bp' => $avg, 'passed' => $passed, 'count' => $count];
    }
}
