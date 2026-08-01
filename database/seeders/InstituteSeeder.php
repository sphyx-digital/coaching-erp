<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\GradeScale;
use App\Models\Institute;
use Illuminate\Database\Seeder;

/**
 * Creates the single institute for this instance from client settings, plus a
 * default active session. Idempotent.
 */
class InstituteSeeder extends Seeder
{
    public function run(): void
    {
        $institute = Institute::firstOrCreate(
            ['name' => (string) client_setting('institute_name', 'Coaching Institute')],
            [
                'gstin' => (string) client_setting('gstin', ''),
                'state_code' => '27',
            ],
        );

        AcademicSession::firstOrCreate(
            ['institute_id' => $institute->id, 'name' => '2026-27'],
            ['is_active' => true, 'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31'],
        );

        $scale = GradeScale::firstOrCreate(
            ['institute_id' => $institute->id, 'name' => 'Default'],
            ['is_active' => true],
        );

        if ($scale->bands()->count() === 0) {
            // Percentage bounds in basis points (10000 = 100%).
            $bands = [
                ['A+', 9000, 10000, 10], ['A', 8000, 8999, 9], ['B', 7000, 7999, 8],
                ['C', 6000, 6999, 7], ['D', 4000, 5999, 6], ['F', 0, 3999, 0],
            ];
            foreach ($bands as [$grade, $min, $max, $points]) {
                $scale->bands()->create(['grade' => $grade, 'min_bp' => $min, 'max_bp' => $max, 'points' => $points]);
            }
        }
    }
}
