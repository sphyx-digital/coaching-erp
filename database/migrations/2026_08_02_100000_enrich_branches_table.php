<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real-world branch/centre profile, rich enough to power the back office AND a
 * public website (locations page, centre detail, SEO). Existing columns
 * (name, code, email, phone, address, city, state, state_code, pincode,
 * is_active) are kept and complemented here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $t) {
            // Identity & classification
            $t->string('slug')->nullable()->after('code');
            $t->string('branch_type', 20)->default('centre')->after('slug'); // main | centre | franchise
            $t->string('legal_name')->nullable()->after('branch_type');

            // Extended contact
            $t->string('alt_phone', 20)->nullable()->after('phone');
            $t->string('whatsapp', 20)->nullable()->after('alt_phone');
            $t->string('support_email')->nullable()->after('email');

            // Structured address + geo
            $t->string('address_line2')->nullable()->after('address');
            $t->string('landmark')->nullable()->after('address_line2');
            $t->string('locality')->nullable()->after('landmark');
            $t->string('country')->default('India')->after('pincode');
            $t->decimal('latitude', 10, 7)->nullable()->after('country');
            $t->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $t->string('google_maps_url')->nullable()->after('longitude');

            // Management & operations
            $t->string('manager_name')->nullable();
            $t->string('manager_phone', 20)->nullable();
            $t->string('manager_email')->nullable();
            $t->date('established_on')->nullable();
            $t->unsignedInteger('student_capacity')->nullable();

            // Legal / finance
            $t->string('gstin', 15)->nullable();
            $t->string('pan', 10)->nullable();
            $t->string('registration_number')->nullable();

            // Website / display (frontend-ready)
            $t->string('tagline')->nullable();
            $t->text('description')->nullable();
            $t->longText('about')->nullable();
            $t->string('hero_image')->nullable();
            $t->string('thumbnail')->nullable();
            $t->json('gallery')->nullable();       // array of image paths
            $t->json('amenities')->nullable();      // ["AC classrooms","Library",...]
            $t->json('highlights')->nullable();     // selling points
            $t->json('social')->nullable();         // {facebook, instagram, youtube, twitter, linkedin, website}
            $t->json('opening_hours')->nullable();  // {mon:{open,close}, ...}
            $t->string('seo_title')->nullable();
            $t->string('seo_description', 500)->nullable();
            $t->string('seo_keywords')->nullable();
            $t->boolean('is_published')->default(false); // visible on the public website
            $t->unsignedInteger('display_order')->default(0);

            $t->unique(['institute_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $t) {
            $t->dropUnique(['institute_id', 'slug']);
            $t->dropColumn([
                'slug', 'branch_type', 'legal_name', 'alt_phone', 'whatsapp', 'support_email',
                'address_line2', 'landmark', 'locality', 'country', 'latitude', 'longitude', 'google_maps_url',
                'manager_name', 'manager_phone', 'manager_email', 'established_on', 'student_capacity',
                'gstin', 'pan', 'registration_number',
                'tagline', 'description', 'about', 'hero_image', 'thumbnail', 'gallery', 'amenities',
                'highlights', 'social', 'opening_hours', 'seo_title', 'seo_description', 'seo_keywords',
                'is_published', 'display_order',
            ]);
        });
    }
};
