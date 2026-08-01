<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured human names (Title/First/Middle/Last) and ISD phone (dial code +
 * mobile), per the platform naming standard. The composed `name` column stays
 * as the display value; existing rows keep their `name` with null parts and are
 * retrofitted as they are edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['students', 'guardians'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('title', 10)->nullable()->after('name');
                $t->string('first_name')->nullable()->after('title');
                $t->string('middle_name')->nullable()->after('first_name');
                $t->string('last_name')->nullable()->after('middle_name');
                $t->string('dial_code', 6)->default('+91')->after('last_name');
            });
        }
    }

    public function down(): void
    {
        foreach (['students', 'guardians'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['title', 'first_name', 'middle_name', 'last_name', 'dial_code']);
            });
        }
    }
};
