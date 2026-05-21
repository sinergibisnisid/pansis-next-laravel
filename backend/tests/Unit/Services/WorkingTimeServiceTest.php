<?php

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\WorkingTime;
use App\Services\WorkingTimeService;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->workingTimeRepository = Mockery::mock(WorkingTimeRepositoryInterface::class);
    $this->workingTimeService = new WorkingTimeService($this->workingTimeRepository);

    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->vault = Vault::factory()->create(['branch_id' => $this->branch->id]);
});

describe('isWithinWorkingTime', function () {
    test('returns true during working hours', function () {
        $currentDay = strtolower(now()->format('l'));

        $schedule = WorkingTime::factory()->make([
            'branch_id' => $this->branch->id,
            'days' => [$currentDay],
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'is_active' => true,
            'type' => 'regular',
        ]);

        $this->workingTimeRepository
            ->shouldReceive('getByBranch')
            ->with($this->branch->id)
            ->andReturn(new Collection([$schedule]));

        $result = $this->workingTimeService->isWithinWorkingTime($this->branch->id);

        expect($result)->toBeTrue();
    });

    test('returns false outside working hours', function () {
        $currentDay = strtolower(now()->format('l'));

        $schedule = WorkingTime::factory()->make([
            'branch_id' => $this->branch->id,
            'days' => [$currentDay],
            'start_time' => '03:00:00',
            'end_time' => '03:01:00',
            'is_active' => true,
            'type' => 'regular',
        ]);

        $this->workingTimeRepository
            ->shouldReceive('getByBranch')
            ->with($this->branch->id)
            ->andReturn(new Collection([$schedule]));

        // Test at noon which is outside 03:00-03:01
        $testTime = now()->setTime(12, 0, 0);

        $result = $this->workingTimeService->isWithinWorkingTime($this->branch->id, null, $testTime);

        expect($result)->toBeFalse();
    });
});

describe('isHoliday', function () {
    test('returns true on holiday', function () {
        $today = now();

        WorkingTime::factory()->create([
            'branch_id' => $this->branch->id,
            'type' => 'holiday',
            'is_active' => true,
            'date' => $today->toDateString(),
            'is_recurring' => false,
        ]);

        $result = $this->workingTimeService->isHoliday($this->branch->id, $today);

        expect($result)->toBeTrue();
    });
});

describe('Timezone handling', function () {
    test('timezone handling with Jakarta time', function () {
        $currentDay = strtolower(now('Asia/Jakarta')->format('l'));
        $jakartaTime = now('Asia/Jakarta');

        $schedule = WorkingTime::factory()->make([
            'branch_id' => $this->branch->id,
            'days' => [$currentDay],
            'start_time' => $jakartaTime->copy()->subHour()->format('H:i:s'),
            'end_time' => $jakartaTime->copy()->addHour()->format('H:i:s'),
            'is_active' => true,
            'type' => 'regular',
        ]);

        $this->workingTimeRepository
            ->shouldReceive('getByBranch')
            ->with($this->branch->id)
            ->andReturn(new Collection([$schedule]));

        $result = $this->workingTimeService->isWithinWorkingTime(
            $this->branch->id,
            null,
            $jakartaTime
        );

        expect($result)->toBeTrue();
    });
});
