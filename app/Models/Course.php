<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'highlights' => 'array',
        'eligibility' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Course $course) {
            if (! $course->slug && $course->name) {
                $course->slug = Str::slug($course->name);
            }
        });
    }

    /** Only courses marked visible on the public website. */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('is_active', true);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
