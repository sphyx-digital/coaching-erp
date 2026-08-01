<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileAttachment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['size' => 'integer'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
