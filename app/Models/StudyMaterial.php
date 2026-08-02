<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyMaterial extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /** A short label + emoji-free icon name for the material type. */
    public function typeLabel(): string
    {
        return [
            'video' => 'Video',
            'note' => 'Note',
            'link' => 'Link',
            'document' => 'Document',
        ][$this->type] ?? 'Document';
    }
}
