<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use App\Support\Access\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquiry extends Model
{
    use BelongsToBranch, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'next_follow_up_on' => 'date',
        'status' => EnquiryStatus::class,
    ];

    /** Open = still in the working pipeline (not converted or lost). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [EnquiryStatus::Converted->value, EnquiryStatus::Lost->value]);
    }

    public function scopeDueBy(Builder $query, string $date): Builder
    {
        return $this->scopeOpen($query)->whereNotNull('next_follow_up_on')->whereDate('next_follow_up_on', '<=', $date);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'counsellor_id');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(EnquiryActivity::class);
    }
}
