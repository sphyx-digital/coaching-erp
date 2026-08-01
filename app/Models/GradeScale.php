<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeScale extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function bands(): HasMany
    {
        return $this->hasMany(GradeScaleBand::class);
    }
}
