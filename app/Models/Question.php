<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'marks' => 'integer',
        'negative_marks' => 'integer',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** The correct option's display text, for review screens. */
    public function correctText(): ?string
    {
        foreach ($this->options ?? [] as $opt) {
            if (($opt['key'] ?? null) === $this->correct_option) {
                return $opt['text'] ?? null;
            }
        }

        return null;
    }
}
