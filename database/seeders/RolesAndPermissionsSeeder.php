<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles, module permissions, and the default role-to-permission map. Idempotent.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** Permission set grouped by module. */
    public const MODULES = [
        'enquiry'    => ['view', 'create', 'update', 'delete'],
        'admission'  => ['view', 'create', 'update', 'delete', 'approve'],
        'batch'      => ['view', 'create', 'update', 'delete'],
        'fee'        => ['view', 'create', 'update', 'delete', 'approve'],
        'attendance' => ['view', 'create', 'update', 'delete'],
        'assessment' => ['view', 'create', 'update', 'delete', 'approve'],
        'report'     => ['view'],
        'settings'   => ['view', 'update'],
    ];

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        $all = [];
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                Permission::findOrCreate($name, 'web');
                $all[] = $name;
            }
        }

        $map = [
            // Platform Admin is a super-admin via Gate::before, and also holds all perms.
            'Platform Admin'  => $all,
            'Institute Admin' => $all,
            // Branch Admin: everything except changing settings (scoped to a branch).
            'Branch Admin'    => array_values(array_diff($all, ['settings.update'])),
            'Counsellor'      => ['enquiry.view', 'enquiry.create', 'enquiry.update', 'enquiry.delete',
                                  'admission.view', 'admission.create', 'admission.update', 'report.view'],
            'Teacher'         => ['batch.view', 'attendance.view', 'attendance.create', 'attendance.update',
                                  'attendance.delete', 'assessment.view', 'assessment.create',
                                  'assessment.update', 'report.view'],
            'Accountant'      => ['fee.view', 'fee.create', 'fee.update', 'fee.delete', 'fee.approve',
                                  'report.view'],
            // Portal roles hold no module permissions; ownership policies govern them.
            'Student'         => [],
            'Parent'          => [],
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        Artisan::call('permission:cache-reset');
    }
}
