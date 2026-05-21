<?php

namespace App\Providers;

use App\Models\AlarmLog;
use App\Models\Device;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultSession;
use App\Observers\AlarmLogObserver;
use App\Observers\DeviceObserver;
use App\Observers\UserObserver;
use App\Observers\VaultObserver;
use App\Observers\VaultSessionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Strict mode in non-production
        Model::shouldBeStrict(!$this->app->isProduction());

        // Prevent lazy loading in non-production
        Model::preventLazyLoading(!$this->app->isProduction());

        // Register observers
        Vault::observe(VaultObserver::class);
        Device::observe(DeviceObserver::class);
        VaultSession::observe(VaultSessionObserver::class);
        AlarmLog::observe(AlarmLogObserver::class);
        User::observe(UserObserver::class);

        // Rate limiters
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('pansin.rate_limits.api', 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(config('pansin.rate_limits.login', 5))
                ->by($request->input('login') . '|' . $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429);
                });
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(config('pansin.rate_limits.otp', 3))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('device-heartbeat', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->header('X-Device-Serial', $request->ip()));
        });
    }
}
