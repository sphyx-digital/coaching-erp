<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1: every migration runs up, down, and up again.
 */
class MigrationReversibilityTest extends TestCase
{
    public function test_migrations_run_up_down_and_up_again(): void
    {
        Artisan::call('migrate', ['--force' => true]);
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('ledger_entries'));
        $this->assertTrue(Schema::hasTable('student_guardian'));

        // down() on every migration
        Artisan::call('migrate:reset', ['--force' => true]);
        $this->assertFalse(Schema::hasTable('invoices'));
        $this->assertFalse(Schema::hasTable('enrollments'));

        // up() again
        Artisan::call('migrate', ['--force' => true]);
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('enrollments'));
    }
}
