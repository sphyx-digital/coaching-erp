<?php

namespace App\Models;

use App\Support\Access\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use BelongsToBranch, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'assessment_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(AssessmentSubject::class);
    }
}
