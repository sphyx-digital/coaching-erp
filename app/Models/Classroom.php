<?php

namespace App\Models;

use App\Support\Access\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classroom extends Model
{
    use BelongsToBranch, HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
