<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bring staff to a real-world HR profile: structured name, contact, employment
 * and personal details, emergency contact. Existing columns (name, email,
 * phone, designation, employee_code, is_active) are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $t) {
            // Structured name
            $t->string('title', 10)->nullable()->after('name');
            $t->string('first_name')->nullable()->after('title');
            $t->string('middle_name')->nullable()->after('first_name');
            $t->string('last_name')->nullable()->after('middle_name');

            // Contact
            $t->string('dial_code', 6)->default('+91')->after('phone');
            $t->string('alt_phone', 20)->nullable()->after('dial_code');

            // Employment
            $t->string('employment_type', 20)->nullable()->after('designation'); // full_time | part_time | visiting | contract
            $t->string('department')->nullable()->after('employment_type');
            $t->date('joining_date')->nullable()->after('department');
            $t->date('exit_date')->nullable()->after('joining_date');
            $t->string('qualification')->nullable()->after('exit_date');
            $t->string('specialization')->nullable()->after('qualification'); // subjects / expertise

            // Personal
            $t->date('dob')->nullable()->after('specialization');
            $t->string('gender', 20)->nullable()->after('dob');
            $t->string('blood_group', 5)->nullable()->after('gender');
            $t->string('photo')->nullable()->after('blood_group');

            // Address
            $t->string('address')->nullable()->after('photo');
            $t->string('city')->nullable()->after('address');
            $t->string('state')->nullable()->after('city');
            $t->string('pincode', 10)->nullable()->after('state');

            // Emergency contact + IDs
            $t->string('emergency_name')->nullable()->after('pincode');
            $t->string('emergency_phone', 20)->nullable()->after('emergency_name');
            $t->string('pan', 10)->nullable()->after('emergency_phone');
            $t->text('notes')->nullable()->after('pan');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $t) {
            $t->dropColumn([
                'title', 'first_name', 'middle_name', 'last_name', 'dial_code', 'alt_phone',
                'employment_type', 'department', 'joining_date', 'exit_date', 'qualification', 'specialization',
                'dob', 'gender', 'blood_group', 'photo', 'address', 'city', 'state', 'pincode',
                'emergency_name', 'emergency_phone', 'pan', 'notes',
            ]);
        });
    }
};
