<?php

namespace App\Livewire\Portal;

use App\Models\Student;
use App\Support\Portal\PortalAccess;
use Illuminate\Support\Facades\Auth;

/**
 * Shared portal behaviour: gates to portal users, resolves the current student,
 * and provides the parent student switcher. Ownership is enforced here so no
 * portal screen can read another family's data.
 */
trait WithCurrentStudent
{
    public ?int $studentId = null;

    protected function initPortal(): void
    {
        abort_unless(Auth::user()?->isPortalUser(), 403);
        $this->studentId = app(PortalAccess::class)->current(Auth::user())?->id;
    }

    public function switchStudent(int $id): void
    {
        $access = app(PortalAccess::class);
        $access->select(Auth::user(), $id); // ignores an unlinked id
        $this->studentId = $access->current(Auth::user())?->id;
    }

    protected function currentStudent(): ?Student
    {
        if (! $this->studentId) {
            return null;
        }
        // Guard against a tampered id: it must belong to this user.
        app(PortalAccess::class)->authorize(Auth::user(), $this->studentId);

        return Student::withoutGlobalScopes()->find($this->studentId);
    }

    protected function accessibleStudents()
    {
        return app(PortalAccess::class)->students(Auth::user());
    }
}
