<?php

namespace App\Support\Portal;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves which students a portal user may see. A student sees only their own
 * record; a parent sees only students linked through StudentGuardian. Every
 * portal query is scoped to one of these students.
 */
class PortalAccess
{
    /** @return Collection<int,Student> */
    public function students(User $user): Collection
    {
        if ($student = $user->studentProfile) {
            return collect([$student]);
        }

        if ($guardian = $user->guardianProfile) {
            return $guardian->students()->get();
        }

        return collect();
    }

    /** The currently selected student (from session), defaulting to the first. */
    public function current(User $user): ?Student
    {
        $students = $this->students($user);
        $id = session('portal_student_id');

        return ($id ? $students->firstWhere('id', $id) : null) ?? $students->first();
    }

    public function select(User $user, int $studentId): void
    {
        if ($this->students($user)->contains('id', $studentId)) {
            session(['portal_student_id' => $studentId]);
        }
    }

    /** Authorize that a student id belongs to this user. */
    public function authorize(User $user, int $studentId): void
    {
        abort_unless($this->students($user)->contains('id', $studentId), 403);
    }
}
