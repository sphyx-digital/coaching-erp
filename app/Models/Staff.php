<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Staff extends Model
{
    use Auditable, HasFactory;

    protected $table = 'staff';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'dob' => 'date',
        'joining_date' => 'date',
        'exit_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Keep the display name in step with the structured name parts.
        static::saving(function (Staff $staff) {
            if ($staff->first_name) {
                $staff->name = trim(collect([$staff->first_name, $staff->middle_name, $staff->last_name])->filter()->implode(' '));
            }
        });
    }

    /** Age today, if DOB is set. */
    public function age(): ?int
    {
        return $this->dob?->age;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_staff');
    }
}
