<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Exam extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
        'shuffle_questions' => 'boolean',
        'negative_marking' => 'boolean',
        'total_marks' => 'integer',
        'pass_percentage' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)
            ->withPivot(['sequence', 'marks'])
            ->withTimestamps()
            ->orderBy('exam_question.sequence');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /** Marks for a question in this exam (pivot override wins over the default). */
    public function marksFor(Question $question): int
    {
        return (int) ($question->pivot->marks ?? $question->marks);
    }

    /** Published and (if a window is set) currently inside it. */
    public function isOpen(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = Carbon::now();

        return ! (($this->starts_at && $now->lt($this->starts_at)) || ($this->ends_at && $now->gt($this->ends_at)));
    }
}
