<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'taxable_value' => 'integer',
        'cgst' => 'integer',
        'sgst' => 'integer',
        'igst' => 'integer',
        'line_total' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeComponent(): BelongsTo
    {
        return $this->belongsTo(FeeComponent::class);
    }
}
