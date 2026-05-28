<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by module
        $permissions = [
            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.manage-roles',

            // Vaults
            'vaults.view',
            'vaults.create',
            'vaults.update',
            'vaults.delete',
            'vaults.open',
            'vaults.close',
            'vaults.monitor',
            // P2-23: hardware command queue (dispatch lock/buzzer commands)
            'vaults.control',

            // Devices
            'devices.view',
            'devices.create',
            'devices.update',
            'devices.delete',
            'devices.register',
            'devices.manage',

            // Alarms
            'alarms.view',
            'alarms.acknowledge',
            'alarms.resolve',
            'alarms.manage',

            // Reports
            'reports.view',
            'reports.generate',
            'reports.download',
            'reports.schedule',

            // Maintenance
            'maintenance.view',
            'maintenance.create',
            'maintenance.update',
            'maintenance.complete',
            'maintenance.manage',

            // Livestream
            'livestream.view',
            'livestream.start',
            'livestream.stop',

            // Monitoring
            'monitoring.view',
            'monitoring.server',
            'monitoring.metrics',

            // Settings
            'settings.view',
            'settings.update',
            'settings.security',

            // Audit
            'audit.view',
            'audit.export',

            // Notifications
            'notifications.view',
            'notifications.manage',
            'notifications.send',

            // Branches
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',

            // Organizations
            'organizations.view',
            'organizations.create',
            'organizations.update',
            'organizations.delete',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        $adminPusat = Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);
        $adminPusat->syncPermissions(
            collect($permissions)->reject(function ($permission) {
                return in_array($permission, ['organizations.delete', 'settings.security']);
            })->values()->toArray()
        );

        $adminCabang = Role::firstOrCreate(['name' => 'Admin Cabang', 'guard_name' => 'web']);
        $adminCabang->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'vaults.view',
            'vaults.create',
            'vaults.update',
            'vaults.delete',
            'vaults.open',
            'vaults.close',
            'vaults.monitor',
            'vaults.control',
            'devices.view',
            'devices.create',
            'devices.update',
            'devices.delete',
            'devices.register',
            'devices.manage',
            'alarms.view',
            'alarms.acknowledge',
            'alarms.resolve',
            'alarms.manage',
            'reports.view',
            'reports.generate',
            'reports.download',
            'reports.schedule',
            'maintenance.view',
            'maintenance.create',
            'maintenance.update',
            'maintenance.complete',
            'maintenance.manage',
            'livestream.view',
            'livestream.start',
            'livestream.stop',
            'monitoring.view',
            'notifications.view',
            'notifications.manage',
        ]);

        $operator = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
        $operator->syncPermissions([
            'vaults.view',
            'vaults.open',
            'vaults.close',
            'vaults.monitor',
            'vaults.control',
            'devices.view',
            'alarms.view',
            'alarms.acknowledge',
            'monitoring.view',
            'livestream.view',
            'livestream.start',
            'livestream.stop',
        ]);

        $security = Role::firstOrCreate(['name' => 'Security', 'guard_name' => 'web']);
        $security->syncPermissions([
            'vaults.view',
            'vaults.monitor',
            'alarms.view',
            'alarms.acknowledge',
            'alarms.resolve',
            'monitoring.view',
            'livestream.view',
            'livestream.start',
            'livestream.stop',
            'audit.view',
        ]);

        $maintenance = Role::firstOrCreate(['name' => 'Maintenance', 'guard_name' => 'web']);
        $maintenance->syncPermissions([
            'devices.view',
            'devices.manage',
            'maintenance.view',
            'maintenance.create',
            'maintenance.update',
            'maintenance.complete',
            'maintenance.manage',
            'reports.view',
            'reports.generate',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'users.view',
            'vaults.view',
            'devices.view',
            'alarms.view',
            'reports.view',
            'maintenance.view',
            'livestream.view',
            'monitoring.view',
            'settings.view',
            'audit.view',
            'notifications.view',
            'branches.view',
            'organizations.view',
        ]);
    }
}
