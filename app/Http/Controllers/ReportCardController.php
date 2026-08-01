<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\GradeScale;
use App\Models\ReportCard;
use App\Models\Student;
use App\Services\Assessments\AssessmentService;
use App\Services\Attendance\AttendanceService;
use App\Support\Portal\PortalAccess;
use Illuminate\Support\Facades\Auth;

class ReportCardController extends Controller
{
    public function show(Assessment $assessment, Student $student, AssessmentService $assessments, AttendanceService $attendance)
    {
        $user = Auth::user();
        $allowed = $user?->can('report.view') || $user?->can('assessment.view')
            || ($user?->isPortalUser() && app(PortalAccess::class)->students($user)->contains('id', $student->id));
        abort_unless($allowed, 403);

        $card = ReportCard::where('student_id', $student->id)->where('assessment_id', $assessment->id)
            ->where('status', 'published')->orderByDesc('version')->first();

        if ($card) {
            $data = [
                'rows' => $card->payload,
                'total' => $card->total_marks,
                'max_total' => $card->max_total,
                'percent_bp' => $card->percentage_bp,
                'grade' => $card->overall_grade,
                'attendance_bp' => $card->attendance_bp,
            ];
        } else {
            $scale = GradeScale::where('is_active', true)->firstOrFail();
            $data = $assessments->computeStudent($assessment, $student->id, $scale);
            $data['attendance_bp'] = $attendance->studentSummary($student->id, $assessment->batch_id)['percent_bp'];
        }

        return view('documents.report-card', [
            'assessment' => $assessment,
            'student' => $student,
            'institute' => current_institute(),
            'data' => $data,
            'card' => $card,
        ]);
    }
}
