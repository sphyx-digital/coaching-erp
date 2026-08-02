<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Services\Fees\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integrity of the full seeded institute: balanced books, gapless numbering,
 * no orphan records.
 */
class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // full DatabaseSeeder: roles, institute, admin, demo institute
    }

    public function test_the_ledger_balances(): void
    {
        $this->assertTrue(app(LedgerService::class)->isBalanced());
        $this->assertSame((int) LedgerEntry::sum('debit'), (int) LedgerEntry::sum('credit'));
        $this->assertGreaterThan(0, LedgerEntry::count()); // there is activity to check
    }

    public function test_receipt_numbers_are_gapless(): void
    {
        $numbers = Payment::whereNotNull('receipt_number')->pluck('receipt_number')
            ->map(fn ($n) => (int) preg_replace('/\D/', '', $n))->sort()->values();

        $this->assertGreaterThan(0, $numbers->count());
        $this->assertSame(range(1, $numbers->count()), $numbers->all()); // 1..N, no gaps
    }

    public function test_there_are_no_orphan_records(): void
    {
        $this->assertSame(0, Enrollment::whereNotIn('student_id', DB::table('students')->pluck('id'))->count());
        $this->assertSame(0, Invoice::whereNotIn('student_id', DB::table('students')->pluck('id'))->count());
        $this->assertSame(0, Payment::whereNotIn('student_id', DB::table('students')->pluck('id'))->count());
    }
}
