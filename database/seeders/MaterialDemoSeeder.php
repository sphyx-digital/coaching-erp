<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudyMaterial;
use Illuminate\Database\Seeder;

/**
 * A few published study materials for the primary course, so the back office
 * and student portal have content. Idempotent: skips if any material exists.
 */
class MaterialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $institute = current_institute();
        if (! $institute || StudyMaterial::count() > 0) {
            return;
        }

        $course = Course::where('institute_id', $institute->id)->orderBy('id')->first();

        $items = [
            ['Kinematics — class notes (PDF)', 'document', 'https://example.com/materials/kinematics.pdf'],
            ['Organic Chemistry basics — lecture', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['Weekly practice sheet — Algebra', 'document', 'https://example.com/materials/algebra-sheet.pdf'],
            ['Exam strategy & time management', 'note', 'https://example.com/materials/exam-strategy'],
        ];

        foreach ($items as $i => [$title, $type, $url]) {
            StudyMaterial::create([
                'institute_id' => $institute->id,
                'academic_session_id' => active_session()?->id,
                'course_id' => $course?->id,
                'title' => $title,
                'type' => $type,
                'url' => $url,
                'is_published' => true,
                'published_at' => now(),
                'display_order' => $i,
            ]);
        }
    }
}
