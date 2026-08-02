<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website-ready course/programme fields, so a course can be presented on the
 * public site (programme cards, detail page, SEO) as well as run the academics.
 * Existing columns (name, code, description, duration_months, is_active) stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $t) {
            $t->string('slug')->nullable()->after('code');
            $t->string('tagline')->nullable()->after('slug');
            $t->string('level', 40)->nullable()->after('tagline');      // Foundation | Class 11 | JEE | NEET ...
            $t->string('mode', 20)->default('offline')->after('level');  // offline | online | hybrid
            $t->string('hero_image')->nullable()->after('description');
            $t->string('thumbnail')->nullable()->after('hero_image');
            $t->json('highlights')->nullable()->after('thumbnail');      // ["Doubt sessions","Weekly tests",...]
            $t->json('eligibility')->nullable();                          // ["Passed Class 10",...]
            $t->unsignedBigInteger('fee_from')->nullable();               // indicative fee, integer paise
            $t->string('seo_title')->nullable();
            $t->string('seo_description', 500)->nullable();
            $t->boolean('is_published')->default(false);
            $t->unsignedInteger('display_order')->default(0);

            $t->unique(['institute_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $t) {
            $t->dropUnique(['institute_id', 'slug']);
            $t->dropColumn([
                'slug', 'tagline', 'level', 'mode', 'hero_image', 'thumbnail',
                'highlights', 'eligibility', 'fee_from', 'seo_title', 'seo_description',
                'is_published', 'display_order',
            ]);
        });
    }
};
