<?php

namespace Database\Seeders;

use App\Enums\WorkingTimeType;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\WorkingTime;
use Illuminate\Database\Seeder;

class WorkingTimeSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'BJB')->first();
        $branches = Branch::where('organization_id', $organization->id)->get();

        foreach ($branches as $branch) {
            // Monday to Friday: 08:00 - 17:00
            for ($day = 1; $day <= 5; $day++) {
                WorkingTime::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'vault_id' => null,
                        'day_of_week' => $day,
                        'type' => WorkingTimeType::Recurring,
                        'is_holiday' => false,
                    ],
                    [
                        'name' => $this->getDayName($day) . ' Working Hours',
                        'start_time' => '08:00:00',
                        'end_time' => '17:00:00',
                        'timezone' => 'Asia/Jakarta',
                        'is_active' => true,
                        'is_holiday' => false,
                        'description' => "Regular working hours for {$this->getDayName($day)}",
                        'metadata' => null,
                    ]
                );
            }

            // Saturday: 08:00 - 12:00
            WorkingTime::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'vault_id' => null,
                    'day_of_week' => 6,
                    'type' => WorkingTimeType::Recurring,
                    'is_holiday' => false,
                ],
                [
                    'name' => 'Saturday Working Hours',
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'timezone' => 'Asia/Jakarta',
                    'is_active' => true,
                    'is_holiday' => false,
                    'description' => 'Half-day working hours for Saturday',
                    'metadata' => null,
                ]
            );
        }

        // National Holidays (applied to all branches)
        $holidays = [
            [
                'name' => 'Tahun Baru 2026',
                'specific_date' => '2026-01-01',
                'description' => 'Hari Tahun Baru Masehi',
            ],
            [
                'name' => 'Isra Miraj Nabi Muhammad SAW',
                'specific_date' => '2026-02-08',
                'description' => 'Isra Miraj Nabi Muhammad SAW 1447 H',
            ],
            [
                'name' => 'Hari Raya Nyepi',
                'specific_date' => '2026-03-19',
                'description' => 'Tahun Baru Saka 1948',
            ],
            [
                'name' => 'Wafat Isa Al Masih',
                'specific_date' => '2026-04-03',
                'description' => 'Jumat Agung',
            ],
            [
                'name' => 'Hari Raya Idul Fitri 1447 H (Hari 1)',
                'specific_date' => '2026-03-30',
                'description' => 'Hari Raya Idul Fitri 1 Syawal 1447 H',
            ],
            [
                'name' => 'Hari Raya Idul Fitri 1447 H (Hari 2)',
                'specific_date' => '2026-03-31',
                'description' => 'Hari Raya Idul Fitri 2 Syawal 1447 H',
            ],
            [
                'name' => 'Hari Buruh Internasional',
                'specific_date' => '2026-05-01',
                'description' => 'Hari Buruh Internasional',
            ],
            [
                'name' => 'Kenaikan Isa Al Masih',
                'specific_date' => '2026-05-14',
                'description' => 'Kenaikan Isa Al Masih',
            ],
            [
                'name' => 'Hari Lahir Pancasila',
                'specific_date' => '2026-06-01',
                'description' => 'Hari Lahir Pancasila',
            ],
            [
                'name' => 'Hari Raya Idul Adha 1447 H',
                'specific_date' => '2026-06-06',
                'description' => 'Hari Raya Idul Adha 10 Dzulhijjah 1447 H',
            ],
            [
                'name' => 'Tahun Baru Islam 1448 H',
                'specific_date' => '2026-06-27',
                'description' => '1 Muharram 1448 H',
            ],
            [
                'name' => 'Hari Kemerdekaan RI',
                'specific_date' => '2026-08-17',
                'description' => 'Hari Ulang Tahun Kemerdekaan Republik Indonesia ke-81',
            ],
            [
                'name' => 'Maulid Nabi Muhammad SAW',
                'specific_date' => '2026-09-05',
                'description' => 'Maulid Nabi Muhammad SAW 1448 H',
            ],
            [
                'name' => 'Hari Natal',
                'specific_date' => '2026-12-25',
                'description' => 'Hari Raya Natal',
            ],
        ];

        foreach ($branches as $branch) {
            foreach ($holidays as $holiday) {
                WorkingTime::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'vault_id' => null,
                        'specific_date' => $holiday['specific_date'],
                        'type' => WorkingTimeType::Holiday,
                        'is_holiday' => true,
                    ],
                    [
                        'name' => $holiday['name'],
                        'day_of_week' => null,
                        'start_time' => '00:00:00',
                        'end_time' => '23:59:59',
                        'timezone' => 'Asia/Jakarta',
                        'is_active' => true,
                        'is_holiday' => true,
                        'description' => $holiday['description'],
                        'metadata' => ['holiday_type' => 'national'],
                    ]
                );
            }
        }
    }

    private function getDayName(int $day): string
    {
        return match ($day) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Unknown',
        };
    }
}
