<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Course;
use App\Support\ClientSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Idempotent public-website content: publishes the primary centre and the
 * existing courses, fills website-ready fields where empty, and sets sensible
 * default global site copy. Safe to run on fresh AND existing databases — it
 * never overwrites values that are already set.
 */
class WebsiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $institute = current_institute();
        if (! $institute) {
            return;
        }

        // Publish and enrich the primary branch.
        $branch = Branch::where('institute_id', $institute->id)->orderBy('id')->first();
        if ($branch) {
            $branch->slug = $branch->slug ?: Str::slug($branch->name);
            $branch->is_published = true;
            $branch->tagline = $branch->tagline ?: 'Expert faculty, small batches, real results';
            $branch->about = $branch->about ?: 'Our centre pairs experienced teachers with focused batches, a well-stocked library and dedicated doubt-clearing zones — a place students actually want to study in.';
            $branch->amenities = $branch->amenities ?: ['AC classrooms', 'Library', 'Wi-Fi', 'Doubt-clearing zone', 'Parking', 'CCTV'];
            $branch->save();
        }

        // Publish and enrich courses with light, sensible defaults.
        $defaults = [
            'JEE' => ['level' => 'JEE', 'fee' => 4500000, 'tag' => 'A head start for JEE Main & Advanced', 'hl' => ['Expert faculty', 'Weekly mock tests', 'Personal mentor', 'Performance analytics']],
            'NEET' => ['level' => 'NEET', 'fee' => 4800000, 'tag' => 'Biology-first NEET preparation', 'hl' => ['NCERT-aligned', 'Daily practice problems', 'Regular assessments', 'One-on-one mentoring']],
        ];

        foreach (Course::where('institute_id', $institute->id)->get() as $course) {
            $d = $defaults[$course->code] ?? null;
            $course->slug = $course->slug ?: Str::slug($course->name);
            $course->is_published = true;
            $course->tagline = $course->tagline ?: ($d['tag'] ?? 'Structured coaching that builds real understanding');
            $course->level = $course->level ?: ($d['level'] ?? null);
            $course->mode = $course->mode ?: 'offline';
            $course->fee_from = $course->fee_from ?: ($d['fee'] ?? null);
            $course->highlights = $course->highlights ?: ($d['hl'] ?? ['Expert faculty', 'Regular assessments', 'Doubt-clearing sessions']);
            $course->description = $course->description ?: 'A structured programme with regular assessments, mentor support and detailed performance tracking.';
            $course->save();
        }

        // Global site copy — only sets a key when it is currently empty.
        $site = app(ClientSettings::class);
        $put = function (string $key, string $value, string $type = 'string') use ($site) {
            if (! $site->get($key)) {
                $site->set($key, $value, $type);
            }
        };
        $put('site_published', '1', 'bool');
        $put('site_headline', 'Coaching that turns effort into results');
        $put('site_subhead', 'Small batches, expert faculty and a proven system for JEE and NEET aspirants.');
        $put('site_about', 'We are a results-driven coaching institute helping students crack India\'s toughest entrance exams. Our teachers, tests and mentoring are built around one goal: your rank.');
        $put('site_cta_label', 'Book a free counselling session');
    }
}
