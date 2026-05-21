<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['code' => 'BJB'],
            [
                'name' => 'Bank BJB',
                'address' => 'Jl. Naripan No. 12-14, Bandung 40111',
                'phone' => '022-4234868',
                'email' => 'info@bankbjb.co.id',
                'logo' => null,
                'is_active' => true,
                'metadata' => [
                    'website' => 'https://www.bankbjb.co.id',
                    'established' => '1961',
                    'type' => 'Bank Pembangunan Daerah',
                ],
            ]
        );
    }
}
