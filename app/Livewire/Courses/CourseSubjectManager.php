<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CourseSubjectManager extends Component
{
    public string $courseName = '';
    public string $courseCode = '';
    public ?int $courseDuration = null;

    public string $subjectName = '';
    public string $subjectCode = '';
    public ?int $subjectCourseId = null;

    public function mount(): void
    {
        // Course and subject management is gated to Institute Admin.
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
    }

    public function addCourse(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        $data = $this->validate([
            'courseName' => ['required', 'string', 'max:255'],
            'courseCode' => ['required', 'string', 'max:30'],
            'courseDuration' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        Course::create([
            'institute_id' => current_institute()?->id,
            'name' => $data['courseName'],
            'code' => $data['courseCode'],
            'duration_months' => $data['courseDuration'],
        ]);

        $this->reset(['courseName', 'courseCode', 'courseDuration']);
    }

    public function addSubject(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        $data = $this->validate([
            'subjectName' => ['required', 'string', 'max:255'],
            'subjectCode' => ['required', 'string', 'max:30'],
            'subjectCourseId' => ['nullable', 'exists:courses,id'],
        ]);

        Subject::create([
            'institute_id' => current_institute()?->id,
            'course_id' => $data['subjectCourseId'],
            'name' => $data['subjectName'],
            'code' => $data['subjectCode'],
        ]);

        $this->reset(['subjectName', 'subjectCode', 'subjectCourseId']);
    }

    public function render()
    {
        return view('livewire.courses.course-subject-manager', [
            'courses' => Course::withCount('subjects')->orderBy('name')->get(),
            'subjects' => Subject::with('course')->orderBy('name')->get(),
            'courseOptions' => Course::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
