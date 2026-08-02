<?php

namespace App\Services\Import;

use App\Models\Guardian;
use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\LedgerService;
use App\Services\Numbering\NumberingService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * File-based client cutover. Validate → preview → commit with a per-row error
 * report and no partial commit; opening balances become opening invoices so the
 * ledger starts balanced; the whole batch can be rolled back.
 */
class ImportService
{
    public const STUDENT_COLUMNS = ['name', 'phone', 'email', 'guardian_name', 'guardian_phone', 'opening_balance'];

    public function __construct(
        private LedgerService $ledger,
        private NumberingService $numbering,
        private AuditLogger $audit,
    ) {}

    /**
     * Validate rows. Returns valid rows and a per-line error map.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array{valid:array, errors:array<int,string>, total:int}
     */
    public function preview(array $rows): array
    {
        $valid = [];
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 1;
            $name = trim((string) ($row['name'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $ob = $row['opening_balance'] ?? '';

            if ($name === '') {
                $errors[$line] = 'Name is required';
            } elseif ($phone !== '' && ! preg_match('/^[0-9]{6,15}$/', $phone)) {
                $errors[$line] = 'Phone must be 6-15 digits';
            } elseif ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[$line] = 'Invalid email';
            } elseif ($ob !== '' && ! is_numeric($ob)) {
                $errors[$line] = 'Opening balance must be a number';
            } else {
                $valid[] = $row;
            }
        }

        return ['valid' => $valid, 'errors' => $errors, 'total' => count($rows)];
    }

    /**
     * Commit an import. Throws (committing nothing) if any row is invalid.
     */
    public function commitStudents(array $rows, string $label, int $instituteId, ?int $branchId = null): ImportBatch
    {
        $preview = $this->preview($rows);
        if (! empty($preview['errors'])) {
            throw new DomainException('Import has '.count($preview['errors']).' invalid row(s); nothing was committed.');
        }

        return DB::transaction(function () use ($preview, $label, $instituteId, $branchId) {
            $batch = ImportBatch::create([
                'institute_id' => $instituteId, 'type' => 'students', 'label' => $label,
                'total_rows' => $preview['total'], 'status' => 'committed',
            ]);

            $imported = 0;
            foreach ($preview['valid'] as $row) {
                $student = Student::create([
                    'institute_id' => $instituteId, 'branch_id' => $branchId, 'import_batch_id' => $batch->id,
                    'name' => trim($row['name']), 'phone' => $row['phone'] ?: null, 'email' => $row['email'] ?: null,
                ]);

                if (! empty($row['guardian_name'])) {
                    $g = Guardian::create(['institute_id' => $instituteId, 'name' => $row['guardian_name'], 'phone' => $row['guardian_phone'] ?? null]);
                    $student->guardians()->attach($g->id, ['is_primary' => true, 'relationship' => 'guardian']);
                }

                $ob = (int) round(((float) ($row['opening_balance'] ?? 0)) * 100);
                if ($ob > 0) {
                    $invoice = Invoice::create([
                        'institute_id' => $instituteId, 'branch_id' => $branchId, 'student_id' => $student->id,
                        'import_batch_id' => $batch->id, 'is_opening' => true,
                        'invoice_number' => $this->numbering->next($instituteId, 'invoice', $branchId),
                        'invoice_date' => now()->toDateString(),
                        'subtotal' => $ob, 'total' => $ob, 'balance' => $ob, 'status' => 'issued',
                    ]);
                    // Opening balance keeps the books balanced from day one.
                    $this->ledger->post([
                        ['account' => LedgerService::ACCT_RECEIVABLE, 'debit' => $ob, 'narration' => 'Opening balance'],
                        ['account' => LedgerService::ACCT_OPENING, 'credit' => $ob],
                    ], $invoice, now()->toDateString(), $instituteId, $branchId);
                }

                $imported++;
            }

            $batch->update(['imported_count' => $imported]);
            $this->audit->log('import.committed', $batch, after: ['imported' => $imported, 'label' => $label]);

            return $batch;
        });
    }

    /** Roll back an import batch: remove its invoices (+ledger) and students. */
    public function rollback(ImportBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach (Invoice::withoutGlobalScopes()->where('import_batch_id', $batch->id)->get() as $invoice) {
                LedgerEntry::where('source_type', $invoice->getMorphClass())->where('source_id', $invoice->id)->delete();
                $invoice->delete();
            }
            Student::withoutGlobalScopes()->where('import_batch_id', $batch->id)->delete();

            $batch->update(['status' => 'rolled_back']);
            $this->audit->log('import.rolled_back', $batch);
        });
    }

    /** @return array{expected:int, imported:int, students:int} */
    public function reconcile(ImportBatch $batch): array
    {
        return [
            'expected' => $batch->total_rows,
            'imported' => $batch->imported_count,
            'students' => Student::withoutGlobalScopes()->where('import_batch_id', $batch->id)->count(),
        ];
    }
}
