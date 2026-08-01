<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * client_settings stub (Phase 0). Holds per-client key/value overrides that
 * take precedence over config/client.php via App\Support\ClientSettings.
 * Phase 1 completes the columns, seeds, and audit columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string|bool|int|json
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_settings');
    }
};
