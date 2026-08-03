<?php

namespace App\Services\Setup;

use App\Models\Branch;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\FeePlan;
use App\Models\Staff;
use App\Models\User;

/**
 * First-run onboarding: detects, from live data, which setup steps an owner has
 * completed so we can guide them from an empty instance to taking their first
 * enquiry. Steps are ordered as a real institute would configure them.
 */
class SetupChecklist
{
    /**
     * @return array<int,array{key:string,title:string,desc:string,url:string,cta:string,icon:string,done:bool,optional:bool}>
     */
    public function steps(User $user): array
    {
        $institute = current_institute();
        $session = active_session();

        $raw = [
            [
                'key' => 'profile',
                'title' => 'Institute profile & branding',
                'desc' => 'Name, logo, brand colour and GSTIN — these appear on invoices, ID cards and the portal.',
                'url' => '/settings', 'cta' => 'Open settings', 'icon' => 'settings',
                'done' => (bool) ($institute && $institute->gstin),
                'optional' => false,
            ],
            [
                'key' => 'branch',
                'title' => 'Add your first centre',
                'desc' => 'Every student, batch and invoice belongs to a branch. Add at least one.',
                'url' => '/branches', 'cta' => 'Add a branch', 'icon' => 'branch',
                'done' => Branch::count() > 0,
                'optional' => false,
            ],
            [
                'key' => 'session',
                'title' => 'Create the academic session',
                'desc' => 'e.g. 2026-27 — scopes batches, fees, attendance and results.',
                'url' => '/sessions', 'cta' => 'Add a session', 'icon' => 'session',
                'done' => (bool) $session,
                'optional' => false,
            ],
            [
                'key' => 'courses',
                'title' => 'Set up courses & subjects',
                'desc' => 'The programmes you offer (JEE, NEET, Foundation…) and their subjects.',
                'url' => '/courses', 'cta' => 'Add courses', 'icon' => 'course',
                'done' => Course::count() > 0,
                'optional' => false,
            ],
            [
                'key' => 'fees',
                'title' => 'Configure fee plans & GST',
                'desc' => 'Fee components and tax rates so you can raise correct, GST-ready invoices.',
                'url' => '/fees/setup', 'cta' => 'Set up fees', 'icon' => 'fees',
                'done' => FeePlan::count() > 0,
                'optional' => false,
            ],
            [
                'key' => 'team',
                'title' => 'Invite your team',
                'desc' => 'Add counsellors, teachers and accountants with the right access.',
                'url' => '/staff', 'cta' => 'Add staff', 'icon' => 'staff',
                'done' => Staff::count() > 1,
                'optional' => false,
            ],
            [
                'key' => 'website',
                'title' => 'Publish your website',
                'desc' => 'Turn on the public site so prospective students can find you and enquire online.',
                'url' => '/website', 'cta' => 'Set up website', 'icon' => 'components',
                'done' => Branch::published()->exists() || Course::published()->exists() || (bool) client_setting('site_published'),
                'optional' => true,
            ],
            [
                'key' => 'security',
                'title' => 'Secure your account',
                'desc' => 'Enable two-factor authentication for admin sign-in.',
                'url' => '/security', 'cta' => 'Enable 2FA', 'icon' => 'settings',
                'done' => $user->hasTwoFactorEnabled(),
                'optional' => true,
            ],
        ];

        return $raw;
    }

    /** @return array{done:int,total:int,percent:int,complete:bool,first_enquiry:bool} */
    public function progress(User $user): array
    {
        $steps = $this->steps($user);
        $required = array_filter($steps, fn ($s) => ! $s['optional']);
        $done = count(array_filter($required, fn ($s) => $s['done']));
        $total = count($required);

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total ? (int) round($done / $total * 100) : 100,
            'complete' => $done === $total,
            'first_enquiry' => Enquiry::exists(),
        ];
    }

    /** Should the dashboard nudge the owner to finish setup? */
    public function shouldNudge(User $user): bool
    {
        return $user->hasAllBranchAccess() && ! $this->progress($user)['complete'];
    }
}
