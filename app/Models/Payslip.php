<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'month' => 'date',
        'unpaid_days' => 'float',
        'gross' => 'integer',
        'lop_amount' => 'integer',
        'fixed_deductions' => 'integer',
        'net' => 'integer',
        'earnings' => 'array',
        'deductions' => 'array',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['finalized', 'paid'], true);
    }
}
