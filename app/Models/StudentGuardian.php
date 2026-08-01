<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot-backed model for the student to guardian link (relationship + primary
 * flag). Ownership scoping for the parent portal runs through this.
 */
class StudentGuardian extends Model
{
    protected $table = 'student_guardian';

    protected $guarded = ['id'];

    protected $casts = ['is_primary' => 'boolean'];
}
