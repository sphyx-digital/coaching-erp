<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    public function sheet(Request $request)
    {
        abort_unless($request->user()?->can('admission.view'), 403);

        $query = Student::query()->where('is_active', true)
            ->with(['branch', 'enrollments.course']);

        $context = 'All active students';

        if ($request->filled('batch')) {
            $batch = Batch::findOrFail($request->integer('batch'));
            $ids = $batch->enrollments()->pluck('student_id');
            $query->whereIn('id', $ids);
            $context = 'Batch: '.$batch->name;
        }

        if ($request->filled('branch')) {
            $query->where('branch_id', $request->integer('branch'));
        }

        return view('documents.id-card-sheet', [
            'students' => $query->orderBy('name')->get(),
            'context' => $context,
            'institute' => current_institute(),
            'session' => active_session(),
        ]);
    }
}
