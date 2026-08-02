<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Course;
use App\Services\Enquiries\EnquiryService;
use Illuminate\Http\Request;

/**
 * The public-facing marketing website, driven entirely by ERP data
 * (published branches and courses) plus editable global content held in
 * client_settings. Enquiries captured here flow straight into the admissions
 * pipeline as online leads.
 */
class PublicSiteController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'institute' => current_institute(),
            'branches' => Branch::published()->orderBy('display_order')->orderBy('name')->get(),
            'courses' => Course::published()->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function branch(string $slug)
    {
        $branch = Branch::published()->where('slug', $slug)->firstOrFail();

        return view('site.branch', [
            'institute' => current_institute(),
            'branch' => $branch,
            'courses' => Course::published()->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function course(string $slug)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();

        return view('site.course', [
            'institute' => current_institute(),
            'course' => $course,
            'branches' => Branch::published()->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function storeEnquiry(Request $request, EnquiryService $enquiries)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'branch_id' => ['required', 'integer'],
            'course_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $institute = current_institute();

        // Only accept published branches/courses from the public form.
        abort_unless(Branch::published()->whereKey($data['branch_id'])->exists(), 422);
        if (! empty($data['course_id'])) {
            abort_unless(Course::published()->whereKey($data['course_id'])->exists(), 422);
        }

        $enquiries->create([
            'institute_id' => $institute?->id,
            'branch_id' => $data['branch_id'],
            'academic_session_id' => active_session()?->id,
            'course_id' => $data['course_id'] ?? null,
            'name' => $data['name'],
            'phone' => preg_replace('/\s+/', '', $data['phone']),
            'email' => $data['email'] ?? null,
            'source' => 'online',
        ]);

        return back()->with('enquiry_ok', true)->withFragment('enquiry');
    }
}
