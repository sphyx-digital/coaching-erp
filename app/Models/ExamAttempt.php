<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ExamAttempt extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'integer',
        'max_score' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'auto_submitted'], true);
    }

    /** Score as a percentage of the maximum (0 when the exam has no marks). */
    public function percentage(): float
    {
        return $this->max_score > 0 ? round(max(0, $this->score) / $this->max_score * 100, 2) : 0.0;
    }

    public function passed(): bool
    {
        return $this->percentage() >= (int) ($this->exam?->pass_percentage ?? 0);
    }

    /** When the attempt window closes: start + duration, capped by the exam end. */
    public function deadline(): ?Carbon
    {
        if (! $this->started_at) {
            return null;
        }

        $byDuration = $this->started_at->copy()->addMinutes((int) $this->exam->duration_minutes);

        if ($this->exam->ends_at && $this->exam->ends_at->lt($byDuration)) {
            return $this->exam->ends_at;
        }

        return $byDuration;
    }
}
