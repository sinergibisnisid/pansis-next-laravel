<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup configuration
    |--------------------------------------------------------------------------
    |
    | Per Pansin Access PDF compliance requirements (audit trail must survive
    | hardware failure), Postgres must be backed up on a recurring schedule
    | with encryption + retention policy.
    */

    /**
     * Disk used for storing backups. Set to 's3' / 'minio' / 'local' depending
     * on environment. Default 'local' is fine for dev; production should use
     * an off-site disk.
     */
    'disk' => env('BACKUP_DISK', 'local'),

    /**
     * Path on the disk where backup files are stored.
     */
    'path' => env('BACKUP_PATH', 'backups'),

    /**
     * Encryption: GPG-style symmetric passphrase. Leave null to disable.
     * Strongly recommended in production. The passphrase must NOT be lost —
     * without it, restoring is impossible.
     */
    'encryption_passphrase' => env('BACKUP_ENCRYPTION_PASSPHRASE'),

    /**
     * Path to the pg_dump and pg_restore binaries. Override if not on PATH
     * (e.g. inside the php container).
     */
    'pg_dump_binary' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
    'pg_restore_binary' => env('BACKUP_PG_RESTORE_BINARY', 'pg_restore'),
    'gpg_binary' => env('BACKUP_GPG_BINARY', 'gpg'),

    /**
     * Retention policy. The cleanup job keeps:
     *   - the N most recent daily backups
     *   - the M most recent weekly backups (one per ISO week)
     *   - the K most recent monthly backups (one per calendar month)
     */
    'retention' => [
        'daily' => (int) env('BACKUP_RETENTION_DAILY', 7),
        'weekly' => (int) env('BACKUP_RETENTION_WEEKLY', 4),
        'monthly' => (int) env('BACKUP_RETENTION_MONTHLY', 12),
    ],

    /**
     * Maximum runtime for a single pg_dump in seconds. Database is large and
     * slow networks can stall a dump; we bail out rather than blocking the
     * worker forever.
     */
    'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 1800),

    /**
     * Schedule. The Console\Kernel reads this and schedules accordingly.
     * Using cron expressions for flexibility per environment.
     */
    'schedule' => [
        'enabled' => (bool) env('BACKUP_SCHEDULE_ENABLED', true),
        'cron' => env('BACKUP_SCHEDULE_CRON', '0 2 * * *'), // daily 02:00 server time
        'cleanup_cron' => env('BACKUP_CLEANUP_CRON', '0 3 * * *'), // daily 03:00
    ],
];
