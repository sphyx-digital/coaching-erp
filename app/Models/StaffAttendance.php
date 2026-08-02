<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use Auditable, HasFactory;

    protected $table = 'staff_attendance';

    protected $guarded = ['id'];

    // Store `date` as a plain Y-m-d string so lookups behave identically on
    // MySQL (DATE column) and SQLite (test DB) — a datetime cast appends a time
    // component under SQLite and breaks exact-date matching.
    protected $casts = [];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
