<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Core\Permissions\Models\Permission;
use App\Core\Permissions\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard', 'buildings', 'floors', 'equipment', 'alarms',
            'energy', 'schedules', 'reports', 'users', 'settings', 'logs',
        ];
        $actions = ['view', 'create', 'edit', 'delete', 'export'];

        $permissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = Permission::firstOrCreate(
                    ['module' => $module, 'action' => $action],
                    ['description' => ucfirst($action) . ' ' . $module]
                );
            }
        }

        $rolePermissions = [
            'admin' => fn($p) => true,
            'manager' => fn($p) => !in_array("{$p->module}.{$p->action}", [
                'users.delete', 'users.create', 'settings.edit', 'settings.create',
                'logs.delete', 'logs.create', 'logs.edit',
            ]),
            'operator' => fn($p) => in_array($p->action, ['view', 'export'])
                || ($p->action === 'edit' && in_array($p->module, ['equipment', 'alarms', 'schedules'])),
            'viewer' => fn($p) => $p->action === 'view'
                && !in_array($p->module, ['users', 'settings', 'logs']),
        ];

        foreach ($rolePermissions as $roleName => $filter) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;
            $ids = collect($permissions)->filter($filter)->pluck('id')->all();
            $role->permissions()->sync($ids);
        }
    }
}
