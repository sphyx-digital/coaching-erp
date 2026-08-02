<?php

namespace App\Services\Fees;

use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Light double-entry ledger. Every fee posting and payment writes balanced
 * entries grouped by a group_ref, so the books reconcile and reversals are
 * compensating (never destructive). Posting refuses to write unbalanced legs.
 */
class LedgerService
{
    public const ACCT_RECEIVABLE = 'fees_receivable';

    public const ACCT_FEE_INCOME = 'fee_income';

    public const ACCT_GST_PAYABLE = 'gst_payable';

    public const ACCT_CASH = 'cash';

    public const ACCT_ADVANCE = 'advance_from_students';

    public const ACCT_REFUND = 'fees_refund';

    public const ACCT_OPENING = 'opening_balance_equity';

    /**
     * Post balanced legs. Each leg: ['account'=>, 'debit'=>int, 'credit'=>int, 'narration'=>?].
     *
     * @param  array<array{account:string,debit?:int,credit?:int,narration?:?string}>  $legs
     */
    public function post(array $legs, Model $source, string $date, ?int $instituteId = null, ?int $branchId = null): string
    {
        $debit = array_sum(array_map(fn ($l) => $l['debit'] ?? 0, $legs));
        $credit = array_sum(array_map(fn ($l) => $l['credit'] ?? 0, $legs));

        if ($debit !== $credit) {
            throw new RuntimeException("Unbalanced ledger post: debit {$debit} != credit {$credit}.");
        }

        $group = (string) Str::uuid();

        foreach ($legs as $leg) {
            LedgerEntry::create([
                'institute_id' => $instituteId,
                'branch_id' => $branchId,
                'entry_date' => $date,
                'group_ref' => $group,
                'account' => $leg['account'],
                'debit' => $leg['debit'] ?? 0,
                'credit' => $leg['credit'] ?? 0,
                'narration' => $leg['narration'] ?? null,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
            ]);
        }

        return $group;
    }

    /** True when total debits equal total credits across the whole ledger. */
    public function isBalanced(?int $instituteId = null): bool
    {
        $q = LedgerEntry::query()->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId));

        return (int) $q->sum('debit') === (int) $q->sum('credit');
    }
}
