<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\Fees\LedgerService;
use App\Services\Import\ImportService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
    }

    private function service(): ImportService
    {
        return app(ImportService::class);
    }

    private function rows(): array
    {
        return [
            ['name' => 'Riya Sharma', 'phone' => '9990001111', 'email' => 'riya@example.com', 'guardian_name' => 'Papa', 'guardian_phone' => '9990002222', 'opening_balance' => '5000'],
            ['name' => 'Arjun Verma', 'phone' => '9990003333', 'email' => '', 'guardian_name' => '', 'guardian_phone' => '', 'opening_balance' => ''],
        ];
    }

    public function test_valid_import_creates_students_and_opening_invoices(): void
    {
        $batch = $this->service()->commitStudents($this->rows(), 'Batch A', $this->institute->id, $this->branch->id);

        $this->assertSame(2, $batch->imported_count);
        $this->assertSame(2, Student::count());
        $this->assertSame(1, Invoice::where('is_opening', true)->count());       // one had an opening balance
        $this->assertSame(500000, (int) Invoice::where('is_opening', true)->value('balance')); // ₹5,000 in paise
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id));
    }

    public function test_a_malformed_row_blocks_the_whole_commit(): void
    {
        $rows = $this->rows();
        $rows[] = ['name' => '', 'phone' => 'abc']; // invalid

        try {
            $this->service()->commitStudents($rows, 'Bad batch', $this->institute->id, $this->branch->id);
            $this->fail('Expected a DomainException');
        } catch (DomainException) {
            // no partial commit
        }

        $this->assertSame(0, Student::count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_preview_reports_per_row_errors(): void
    {
        $rows = [['name' => 'OK'], ['name' => '', 'phone' => 'x'], ['name' => 'Bad', 'email' => 'nope']];
        $preview = $this->service()->preview($rows);

        $this->assertCount(1, $preview['valid']);
        $this->assertArrayHasKey(2, $preview['errors']); // line 2 no name
        $this->assertArrayHasKey(3, $preview['errors']); // line 3 bad email
    }

    public function test_rollback_removes_imported_records(): void
    {
        $batch = $this->service()->commitStudents($this->rows(), 'Batch A', $this->institute->id, $this->branch->id);

        $this->service()->rollback($batch);

        $this->assertSame(0, Student::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame('rolled_back', $batch->fresh()->status);
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id)); // stays balanced (0)
    }

    public function test_reconciliation_counts_match(): void
    {
        $batch = $this->service()->commitStudents($this->rows(), 'Batch A', $this->institute->id, $this->branch->id);
        $r = $this->service()->reconcile($batch);

        $this->assertSame(2, $r['expected']);
        $this->assertSame(2, $r['imported']);
        $this->assertSame(2, $r['students']);
    }
}
