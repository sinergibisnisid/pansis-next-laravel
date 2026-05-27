<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function general(): JsonResponse
    {
        $settings = [
            'app_name' => config('app.name'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'session_timeout_minutes' => config('pansin.session_timeout', 30),
            'max_login_attempts' => config('pansin.max_login_attempts', 5),
            'lockout_duration_minutes' => config('pansin.lockout_duration', 15),
            'default_pagination' => config('pansin.default_pagination', 15),
        ];

        return $this->successResponse($settings, 'General settings retrieved');
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_name' => 'sometimes|string|max:255',
            'timezone' => 'sometimes|string|timezone',
            'locale' => 'sometimes|string|in:id,en',
            'session_timeout_minutes' => 'sometimes|integer|min:5|max:120',
            'max_login_attempts' => 'sometimes|integer|min:3|max:10',
            'lockout_duration_minutes' => 'sometimes|integer|min:5|max:60',
            'default_pagination' => 'sometimes|integer|min:10|max:100',
        ]);

        foreach ($data as $key => $value) {
            setting(["pansin.{$key}" => $value]);
        }

        return $this->successResponse($data, 'General settings updated');
    }

    public function notifications(): JsonResponse
    {
        $settings = [
            'email_enabled' => config('pansin.notifications.email_enabled', true),
            'sms_enabled' => config('pansin.notifications.sms_enabled', false),
            'whatsapp_enabled' => config('pansin.notifications.whatsapp_enabled', false),
            'push_enabled' => config('pansin.notifications.push_enabled', true),
            'telegram_enabled' => config('pansin.notifications.telegram_enabled', false),
            'alarm_notification_delay_seconds' => config('pansin.notifications.alarm_delay', 0),
            'digest_frequency' => config('pansin.notifications.digest_frequency', 'daily'),
        ];

        return $this->successResponse($settings, 'Notification settings retrieved');
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'whatsapp_enabled' => 'sometimes|boolean',
            'push_enabled' => 'sometimes|boolean',
            'telegram_enabled' => 'sometimes|boolean',
            'alarm_notification_delay_seconds' => 'sometimes|integer|min:0|max:300',
            'digest_frequency' => 'sometimes|string|in:realtime,hourly,daily,weekly',
        ]);

        foreach ($data as $key => $value) {
            setting(["pansin.notifications.{$key}" => $value]);
        }

        return $this->successResponse($data, 'Notification settings updated');
    }

    public function security(): JsonResponse
    {
        $settings = [
            'two_factor_enabled' => config('pansin.security.two_factor_enabled', true),
            'two_factor_method' => config('pansin.security.two_factor_method', 'otp'),
            'password_expiry_days' => config('pansin.security.password_expiry_days', 90),
            'password_min_length' => config('pansin.security.password_min_length', 8),
            'require_uppercase' => config('pansin.security.require_uppercase', true),
            'require_lowercase' => config('pansin.security.require_lowercase', true),
            'require_number' => config('pansin.security.require_number', true),
            'require_symbol' => config('pansin.security.require_symbol', true),
            'session_concurrent_limit' => config('pansin.security.session_concurrent_limit', 3),
            'ip_whitelist_enabled' => config('pansin.security.ip_whitelist_enabled', false),
            'ip_whitelist' => config('pansin.security.ip_whitelist', []),
        ];

        return $this->successResponse($settings, 'Security settings retrieved');
    }

    public function updateSecurity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'two_factor_enabled' => 'sometimes|boolean',
            'two_factor_method' => 'sometimes|string|in:otp,email,sms',
            'password_expiry_days' => 'sometimes|integer|min:30|max:365',
            'password_min_length' => 'sometimes|integer|min:8|max:32',
            'require_uppercase' => 'sometimes|boolean',
            'require_lowercase' => 'sometimes|boolean',
            'require_number' => 'sometimes|boolean',
            'require_symbol' => 'sometimes|boolean',
            'session_concurrent_limit' => 'sometimes|integer|min:1|max:10',
            'ip_whitelist_enabled' => 'sometimes|boolean',
            'ip_whitelist' => 'sometimes|array',
            'ip_whitelist.*' => 'ip',
        ]);

        foreach ($data as $key => $value) {
            setting(["pansin.security.{$key}" => $value]);
        }

        return $this->successResponse($data, 'Security settings updated');
    }
}
