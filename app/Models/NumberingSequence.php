<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A gapless per-scope document counter. Incremented under a row lock by the
 * numbering service (Phase 3). Not edited by hand.
 */
class NumberingSequence extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'next_number' => 'integer',
        'padding' => 'integer',
    ];
}
