<?php

namespace Database\Seeders;

use App\Enums\VaultStatus;
use App\Enums\VaultType;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use Illuminate\Database\Seeder;

class VaultSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'BJB')->first();
        $branches = Branch::where('organization_id', $organization->id)->get()->keyBy('code');

        $vaults = [
            // Kantor Pusat
            [
                'branch_code' => 'KP',
                'name' => 'Vault Utama Kantor Pusat',
                'code' => 'KP-V001',
                'type' => VaultType::Main,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room A-01',
                'max_session_duration_minutes' => 30,
                'is_active' => true,
                'metadata' => ['capacity' => 'large', 'security_level' => 'high'],
            ],
            [
                'branch_code' => 'KP',
                'name' => 'Vault Sekunder Kantor Pusat',
                'code' => 'KP-V002',
                'type' => VaultType::Secondary,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room A-02',
                'max_session_duration_minutes' => 20,
                'is_active' => true,
                'metadata' => ['capacity' => 'medium', 'security_level' => 'high'],
            ],
            [
                'branch_code' => 'KP',
                'name' => 'ATM Vault Kantor Pusat',
                'code' => 'KP-V003',
                'type' => VaultType::Atm,
                'status' => VaultStatus::Closed,
                'floor' => '1',
                'room' => 'Room B-01',
                'max_session_duration_minutes' => 15,
                'is_active' => true,
                'metadata' => ['capacity' => 'small', 'security_level' => 'medium'],
            ],

            // Cabang Bandung
            [
                'branch_code' => 'BDG',
                'name' => 'Vault Utama Bandung',
                'code' => 'BDG-V001',
                'type' => VaultType::Main,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room V-01',
                'max_session_duration_minutes' => 30,
                'is_active' => true,
                'metadata' => ['capacity' => 'large', 'security_level' => 'high'],
            ],
            [
                'branch_code' => 'BDG',
                'name' => 'Vault Sekunder Bandung',
                'code' => 'BDG-V002',
                'type' => VaultType::Secondary,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room V-02',
                'max_session_duration_minutes' => 20,
                'is_active' => true,
                'metadata' => ['capacity' => 'medium', 'security_level' => 'medium'],
            ],
            [
                'branch_code' => 'BDG',
                'name' => 'ATM Vault Bandung',
                'code' => 'BDG-V003',
                'type' => VaultType::Atm,
                'status' => VaultStatus::Closed,
                'floor' => '1',
                'room' => 'Room ATM-01',
                'max_session_duration_minutes' => 15,
                'is_active' => true,
                'metadata' => ['capacity' => 'small', 'security_level' => 'medium'],
            ],

            // Cabang Jakarta
            [
                'branch_code' => 'JKT',
                'name' => 'Vault Utama Jakarta',
                'code' => 'JKT-V001',
                'type' => VaultType::Main,
                'status' => VaultStatus::Closed,
                'floor' => 'B2',
                'room' => 'Room V-01',
                'max_session_duration_minutes' => 30,
                'is_active' => true,
                'metadata' => ['capacity' => 'large', 'security_level' => 'high'],
            ],
            [
                'branch_code' => 'JKT',
                'name' => 'Vault Sekunder Jakarta',
                'code' => 'JKT-V002',
                'type' => VaultType::Secondary,
                'status' => VaultStatus::Closed,
                'floor' => 'B2',
                'room' => 'Room V-02',
                'max_session_duration_minutes' => 20,
                'is_active' => true,
                'metadata' => ['capacity' => 'medium', 'security_level' => 'high'],
            ],

            // Cabang Surabaya
            [
                'branch_code' => 'SBY',
                'name' => 'Vault Utama Surabaya',
                'code' => 'SBY-V001',
                'type' => VaultType::Main,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room V-01',
                'max_session_duration_minutes' => 30,
                'is_active' => true,
                'metadata' => ['capacity' => 'large', 'security_level' => 'high'],
            ],
            [
                'branch_code' => 'SBY',
                'name' => 'ATM Vault Surabaya',
                'code' => 'SBY-V002',
                'type' => VaultType::Atm,
                'status' => VaultStatus::Closed,
                'floor' => '1',
                'room' => 'Room ATM-01',
                'max_session_duration_minutes' => 15,
                'is_active' => true,
                'metadata' => ['capacity' => 'small', 'security_level' => 'medium'],
            ],

            // Cabang Semarang
            [
                'branch_code' => 'SMG',
                'name' => 'Vault Utama Semarang',
                'code' => 'SMG-V001',
                'type' => VaultType::Main,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room V-01',
                'max_session_duration_minutes' => 30,
                'is_active' => true,
                'metadata' => ['capacity' => 'large', 'security_level' => 'high'],
            ],
            [
                'branch_code' => 'SMG',
                'name' => 'Vault Sekunder Semarang',
                'code' => 'SMG-V002',
                'type' => VaultType::Secondary,
                'status' => VaultStatus::Closed,
                'floor' => 'B1',
                'room' => 'Room V-02',
                'max_session_duration_minutes' => 20,
                'is_active' => true,
                'metadata' => ['capacity' => 'medium', 'security_level' => 'medium'],
            ],
        ];

        foreach ($vaults as $vaultData) {
            $branchCode = $vaultData['branch_code'];
            unset($vaultData['branch_code']);

            Vault::firstOrCreate(
                ['code' => $vaultData['code']],
                array_merge($vaultData, [
                    'branch_id' => $branches[$branchCode]->id,
                ])
            );
        }
    }
}
