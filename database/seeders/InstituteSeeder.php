<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
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
    }
}
