<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'BJB')->first();
        $branches = Branch::where('organization_id', $organization->id)->get()->keyBy('code');

        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branches['KP']->id,
                'email' => 'superadmin@bankbjb.co.id',
                'password' => Hash::make('P@nsin2024!'),
                'full_name' => 'Super Administrator',
                'phone' => '08121000001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        // Admin Pusat
        $adminPusat = User::firstOrCreate(
            ['username' => 'adminpusat'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branches['KP']->id,
                'email' => 'adminpusat@bankbjb.co.id',
                'password' => Hash::make('P@nsin2024!'),
                'full_name' => 'Admin Pusat',
                'phone' => '08121000002',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminPusat->assignRole('Admin Pusat');

        // Admin Cabang for each branch (except KP which uses Admin Pusat)
        $adminCabangData = [
            'BDG' => [
                'username' => 'admin.bdg',
                'email' => 'admin.bdg@bankbjb.co.id',
                'full_name' => 'Admin Cabang Bandung',
                'phone' => '08121000010',
            ],
            'JKT' => [
                'username' => 'admin.jkt',
                'email' => 'admin.jkt@bankbjb.co.id',
                'full_name' => 'Admin Cabang Jakarta',
                'phone' => '08121000011',
            ],
            'SBY' => [
                'username' => 'admin.sby',
                'email' => 'admin.sby@bankbjb.co.id',
                'full_name' => 'Admin Cabang Surabaya',
                'phone' => '08121000012',
            ],
            'SMG' => [
                'username' => 'admin.smg',
                'email' => 'admin.smg@bankbjb.co.id',
                'full_name' => 'Admin Cabang Semarang',
                'phone' => '08121000013',
            ],
        ];

        foreach ($adminCabangData as $branchCode => $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'organization_id' => $organization->id,
                    'branch_id' => $branches[$branchCode]->id,
                    'email' => $data['email'],
                    'password' => Hash::make('P@nsin2024!'),
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('Admin Cabang');
        }

        // Operators
        $operators = [
            [
                'username' => 'operator.bdg1',
                'email' => 'operator.bdg1@bankbjb.co.id',
                'full_name' => 'Operator Bandung 1',
                'phone' => '08121000020',
                'branch_code' => 'BDG',
            ],
            [
                'username' => 'operator.jkt1',
                'email' => 'operator.jkt1@bankbjb.co.id',
                'full_name' => 'Operator Jakarta 1',
                'phone' => '08121000021',
                'branch_code' => 'JKT',
            ],
            [
                'username' => 'operator.sby1',
                'email' => 'operator.sby1@bankbjb.co.id',
                'full_name' => 'Operator Surabaya 1',
                'phone' => '08121000022',
                'branch_code' => 'SBY',
            ],
        ];

        foreach ($operators as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'organization_id' => $organization->id,
                    'branch_id' => $branches[$data['branch_code']]->id,
                    'email' => $data['email'],
                    'password' => Hash::make('P@nsin2024!'),
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('Operator');
        }

        // Security
        $securityUsers = [
            [
                'username' => 'security.bdg1',
                'email' => 'security.bdg1@bankbjb.co.id',
                'full_name' => 'Security Bandung 1',
                'phone' => '08121000030',
                'branch_code' => 'BDG',
            ],
            [
                'username' => 'security.jkt1',
                'email' => 'security.jkt1@bankbjb.co.id',
                'full_name' => 'Security Jakarta 1',
                'phone' => '08121000031',
                'branch_code' => 'JKT',
            ],
        ];

        foreach ($securityUsers as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'organization_id' => $organization->id,
                    'branch_id' => $branches[$data['branch_code']]->id,
                    'email' => $data['email'],
                    'password' => Hash::make('P@nsin2024!'),
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('Security');
        }

        // Maintenance
        $maintenanceUsers = [
            [
                'username' => 'maintenance.bdg1',
                'email' => 'maintenance.bdg1@bankbjb.co.id',
                'full_name' => 'Teknisi Bandung 1',
                'phone' => '08121000040',
                'branch_code' => 'BDG',
            ],
            [
                'username' => 'maintenance.jkt1',
                'email' => 'maintenance.jkt1@bankbjb.co.id',
                'full_name' => 'Teknisi Jakarta 1',
                'phone' => '08121000041',
                'branch_code' => 'JKT',
            ],
        ];

        foreach ($maintenanceUsers as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'organization_id' => $organization->id,
                    'branch_id' => $branches[$data['branch_code']]->id,
                    'email' => $data['email'],
                    'password' => Hash::make('P@nsin2024!'),
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('Maintenance');
        }

        // Viewer
        $viewer = User::firstOrCreate(
            ['username' => 'viewer.kp1'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branches['KP']->id,
                'email' => 'viewer.kp1@bankbjb.co.id',
                'password' => Hash::make('P@nsin2024!'),
                'full_name' => 'Viewer Kantor Pusat',
                'phone' => '08121000050',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $viewer->assignRole('Viewer');
    }
}
