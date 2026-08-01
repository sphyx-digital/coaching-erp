<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ----- Profile links -------------------------------------------------

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function guardianProfile(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    // ----- Access helpers ------------------------------------------------

    /** Admins see every branch; other staff are branch-limited. */
    public function hasAllBranchAccess(): bool
    {
        return $this->hasAnyRole(['Platform Admin', 'Institute Admin']);
    }

    /** A portal user reads only their own (or linked) records, not by branch. */
    public function isPortalUser(): bool
    {
        return $this->hasAnyRole(['Student', 'Parent']);
    }

    public function isBranchLimitedStaff(): bool
    {
        return ! $this->hasAllBranchAccess() && ! $this->isPortalUser();
    }

    /**
     * Branch ids this user may see: the staff primary branch plus any
     * additionally assigned branches. Empty means "no branch, sees nothing".
     *
     * @return array<int>
     */
    public function branchIds(): array
    {
        $staff = $this->staff()->with('branches:id')->first();

        if (! $staff) {
            return [];
        }

        $ids = $staff->branches->pluck('id')->all();

        if ($staff->branch_id) {
            $ids[] = $staff->branch_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Student ids this portal user may read: their own (student) or their
     * linked children (parent).
     *
     * @return array<int>
     */
    public function accessibleStudentIds(): array
    {
        if ($student = $this->studentProfile) {
            return [$student->id];
        }

        if ($guardian = $this->guardianProfile) {
            return $guardian->students()->pluck('students.id')->map(fn ($id) => (int) $id)->all();
        }

        return [];
    }
}
