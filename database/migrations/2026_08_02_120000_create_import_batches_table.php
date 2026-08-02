<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import batches for client cutover. Each import is a labelled batch that can be
 * rolled back; records it created carry import_batch_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);        // students ...
            $table->string('label');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('committed'); // committed | rolled_back
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->auditColumns();
        });

        Schema::table('students', function (Blueprint $t) {
            $t->foreignId('import_batch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('invoices', function (Blueprint $t) {
            $t->foreignId('import_batch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $t->boolean('is_opening')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $t) {
            $t->dropConstrainedForeignId('import_batch_id');
            $t->dropColumn('is_opening');
        });
        Schema::table('students', function (Blueprint $t) {
            $t->dropConstrainedForeignId('import_batch_id');
        });
        Schema::dropIfExists('import_batches');
    }
};
