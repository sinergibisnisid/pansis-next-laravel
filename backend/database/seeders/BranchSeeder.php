<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'BJB')->first();

        $branches = [
            [
                'name' => 'Kantor Pusat',
                'code' => 'KP',
                'address' => 'Jl. Naripan No. 12-14, Bandung 40111',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'phone' => '022-4234868',
                'email' => 'pusat@bankbjb.co.id',
                'latitude' => -6.91745000,
                'longitude' => 107.60980000,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'metadata' => ['type' => 'head_office'],
            ],
            [
                'name' => 'Cabang Bandung',
                'code' => 'BDG',
                'address' => 'Jl. Braga No. 45, Bandung 40111',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'phone' => '022-4201234',
                'email' => 'cabang.bandung@bankbjb.co.id',
                'latitude' => -6.91750000,
                'longitude' => 107.60970000,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'metadata' => ['type' => 'branch'],
            ],
            [
                'name' => 'Cabang Jakarta',
                'code' => 'JKT',
                'address' => 'Jl. Jend. Sudirman Kav. 52-53, Jakarta 12190',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'phone' => '021-5151234',
                'email' => 'cabang.jakarta@bankbjb.co.id',
                'latitude' => -6.22740000,
                'longitude' => 106.80200000,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'metadata' => ['type' => 'branch'],
            ],
            [
                'name' => 'Cabang Surabaya',
                'code' => 'SBY',
                'address' => 'Jl. Pemuda No. 27-31, Surabaya 60271',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'phone' => '031-5341234',
                'email' => 'cabang.surabaya@bankbjb.co.id',
                'latitude' => -7.26570000,
                'longitude' => 112.74080000,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'metadata' => ['type' => 'branch'],
            ],
            [
                'name' => 'Cabang Semarang',
                'code' => 'SMG',
                'address' => 'Jl. Pandanaran No. 88, Semarang 50134',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'phone' => '024-8411234',
                'email' => 'cabang.semarang@bankbjb.co.id',
                'latitude' => -6.98390000,
                'longitude' => 110.41030000,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'metadata' => ['type' => 'branch'],
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $branchData['code'],
                ],
                $branchData
            );
        }
    }
}
