<?php

namespace App\Services\Numbering;

use App\Models\NumberingSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Issues gapless, sequential document numbers per (institute, branch, session,
 * doc type). The row is locked FOR UPDATE inside a transaction, so two
 * concurrent callers can never receive the same number or leave a gap.
 * Sequence rows are never edited by hand.
 */
class NumberingService
{
    /** Default prefixes per document type; overridden by the stored sequence row. */
    public const DEFAULT_PREFIXES = [
        'admission' => 'ADM',
        'enquiry' => 'ENQ',
        'invoice' => 'INV',
        'receipt' => 'RCPT',
        'refund' => 'REF',
    ];

    /**
     * Reserve and return the next formatted number for a document series.
     */
    public function next(int $instituteId, string $docType, ?int $branchId = null, ?int $sessionId = null): string
    {
        $scope = [
            'institute_id' => $instituteId,
            'branch_id' => $branchId,
            'academic_session_id' => $sessionId,
            'doc_type' => $docType,
        ];

        // Ensure the row exists (idempotent; unique index guards the race).
        $this->ensureRow($scope, $docType);

        return DB::transaction(function () use ($scope) {
            $seq = NumberingSequence::query()
                ->where('institute_id', $scope['institute_id'])
                ->where('doc_type', $scope['doc_type'])
                ->where(fn ($q) => $this->matchNullable($q, 'branch_id', $scope['branch_id']))
                ->where(fn ($q) => $this->matchNullable($q, 'academic_session_id', $scope['academic_session_id']))
                ->lockForUpdate()
                ->first();

            $number = (int) $seq->next_number;
            $seq->next_number = $number + 1;
            $seq->save();

            return $this->format($seq->prefix, $number, (int) $seq->padding);
        });
    }

    public function format(string $prefix, int $number, int $padding): string
    {
        $padded = str_pad((string) $number, max($padding, 1), '0', STR_PAD_LEFT);

        return $prefix === '' ? $padded : "{$prefix}/{$padded}";
    }

    private function ensureRow(array $scope, string $docType): void
    {
        if ($this->find($scope)) {
            return;
        }

        try {
            NumberingSequence::create($scope + [
                'prefix' => self::DEFAULT_PREFIXES[$docType] ?? strtoupper($docType),
                'next_number' => 1,
                'padding' => 4,
            ]);
        } catch (QueryException $e) {
            // A concurrent caller created it first; the unique index did its job.
            if (! $this->find($scope)) {
                throw $e;
            }
        }
    }

    private function find(array $scope): ?NumberingSequence
    {
        return NumberingSequence::query()
            ->where('institute_id', $scope['institute_id'])
            ->where('doc_type', $scope['doc_type'])
            ->where(fn ($q) => $this->matchNullable($q, 'branch_id', $scope['branch_id']))
            ->where(fn ($q) => $this->matchNullable($q, 'academic_session_id', $scope['academic_session_id']))
            ->first();
    }

    private function matchNullable($query, string $column, ?int $value): void
    {
        $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }
}
