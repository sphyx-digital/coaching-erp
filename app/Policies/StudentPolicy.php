<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Support\Access\ChecksBranchAccess;

/**
 * Authorization for student records (PII). Portal users (Student/Parent) may
 * only read their own or their linked students; staff need admission permission
 * and branch access.
 */
class StudentPolicy
{
    use ChecksBranchAccess;

    public function viewAny(User $user): bool
    {
        return $user->isPortalUser() || $user->can('admission.view');
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isPortalUser()) {
            return in_array($student->id, $user->accessibleStudentIds(), true);
        }

        return $user->can('admission.view') && $this->branchAllows($user, $student->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('admission.create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('admission.update') && $this->branchAllows($user, $student->branch_id);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('admission.delete') && $this->branchAllows($user, $student->branch_id);
    }
}
