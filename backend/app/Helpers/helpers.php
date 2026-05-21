<?php

use Illuminate\Support\Carbon;

if (!function_exists('format_duration')) {
    function format_duration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        }
        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        }
        return sprintf('%ds', $secs);
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('mask_email')) {
    function mask_email(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 0));
        return $maskedName . '@' . $domain;
    }
}

if (!function_exists('mask_phone')) {
    function mask_phone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) return $phone;
        return substr($phone, 0, 4) . str_repeat('*', $length - 8) . substr($phone, -4);
    }
}

if (!function_exists('generate_device_token')) {
    function generate_device_token(): string
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('is_valid_uuid')) {
    function is_valid_uuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}

if (!function_exists('now_jakarta')) {
    function now_jakarta(): Carbon
    {
        return Carbon::now('Asia/Jakarta');
    }
}

if (!function_exists('to_jakarta_time')) {
    function to_jakarta_time(Carbon|string $dateTime): Carbon
    {
        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }
        return $dateTime->setTimezone('Asia/Jakarta');
    }
}
