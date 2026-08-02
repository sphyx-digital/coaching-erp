<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_from' => 'date',
        'earnings' => 'array',
        'deductions' => 'array',
        'is_active' => 'boolean',
        'monthly_gross' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function totalDeductions(): int
    {
        return collect($this->deductions ?? [])->sum('amount');
    }
}
