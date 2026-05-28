<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Postgres backup orchestration.
 *
 *  - run()          : pg_dump → optional GPG symmetric encrypt → upload to disk.
 *  - restore()      : (admin-only) download → optional decrypt → pg_restore.
 *  - cleanup()      : enforce retention policy (daily/weekly/monthly).
 *  - list()         : enumerate all backups currently on the configured disk.
 *
 * Backup file naming: pansin-{YYYYmmdd_HHiiss}.dump[.gpg]
 *
 * Encryption is symmetric GPG, passphrase from BACKUP_ENCRYPTION_PASSPHRASE.
 * If the passphrase is null we skip encryption — fine for dev, NOT for prod.
 */
class BackupService
{
    public function __construct(
        private readonly string $disk = 'local',
        private readonly string $path = 'backups',
    ) {}

    /**
     * Run a fresh backup. Returns path on the configured disk.
     */
    public function run(): array
    {
        $start = microtime(true);
        $disk = Storage::disk(config('backup.disk', $this->disk));
        $path = config('backup.path', $this->path);

        $timestamp = now()->format('Ymd_His');
        $rawFilename = "pansin-{$timestamp}.dump";
        $tmpDir = storage_path('app/tmp/backups');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $rawPath = $tmpDir . DIRECTORY_SEPARATOR . $rawFilename;

        try {
            $this->runPgDump($rawPath);
            $sizeRaw = filesize($rawPath) ?: 0;

            $finalPath = $rawPath;
            $finalFilename = $rawFilename;
            $encrypted = false;

            $passphrase = config('backup.encryption_passphrase');
            if (!empty($passphrase)) {
                $encryptedPath = $rawPath . '.gpg';
                $this->runGpgEncrypt($rawPath, $encryptedPath, $passphrase);
                @unlink($rawPath);
                $finalPath = $encryptedPath;
                $finalFilename = $rawFilename . '.gpg';
                $encrypted = true;
            }

            $sizeFinal = filesize($finalPath) ?: 0;
            $remotePath = trim($path, '/') . '/' . $finalFilename;

            $stream = fopen($finalPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException("Cannot open backup file for upload: {$finalPath}");
            }
            $disk->put($remotePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($finalPath);

            $durationSeconds = round(microtime(true) - $start, 2);

            Log::info('Backup completed', [
                'file' => $remotePath,
                'size_bytes' => $sizeFinal,
                'raw_size_bytes' => $sizeRaw,
                'encrypted' => $encrypted,
                'disk' => config('backup.disk', $this->disk),
                'duration_seconds' => $durationSeconds,
            ]);

            return [
                'path' => $remotePath,
                'filename' => $finalFilename,
                'size_bytes' => $sizeFinal,
                'encrypted' => $encrypted,
                'disk' => config('backup.disk', $this->disk),
                'duration_seconds' => $durationSeconds,
                'created_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            // Clean up any partial files.
            if (isset($rawPath) && file_exists($rawPath)) {
                @unlink($rawPath);
            }
            if (isset($encryptedPath) && file_exists($encryptedPath)) {
                @unlink($encryptedPath);
            }
            Log::error('Backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Apply retention policy. Returns the list of removed backup paths.
     */
    public function cleanup(): array
    {
        $disk = Storage::disk(config('backup.disk', $this->disk));
        $path = config('backup.path', $this->path);

        $files = collect($disk->files($path))
            ->filter(fn ($file) => Str::contains($file, 'pansin-'))
            ->sortDesc()
            ->values();

        // Group files into daily / weekly / monthly buckets and apply retention.
        $retention = config('backup.retention');
        $keepDaily = (int) ($retention['daily'] ?? 7);
        $keepWeekly = (int) ($retention['weekly'] ?? 4);
        $keepMonthly = (int) ($retention['monthly'] ?? 12);

        $byDay = [];
        $byWeek = [];
        $byMonth = [];

        foreach ($files as $file) {
            $ts = $this->parseTimestampFromFilename(basename($file));
            if (!$ts) continue;
            $byDay[$ts->format('Y-m-d')][] = $file;
            $byWeek[$ts->format('o-W')][] = $file;
            $byMonth[$ts->format('Y-m')][] = $file;
        }

        $keep = collect();
        $keep = $keep->merge(
            collect($byDay)->take($keepDaily)->map(fn ($files) => $files[0])
        );
        $keep = $keep->merge(
            collect($byWeek)->take($keepWeekly)->map(fn ($files) => $files[0])
        );
        $keep = $keep->merge(
            collect($byMonth)->take($keepMonthly)->map(fn ($files) => $files[0])
        );
        $keep = $keep->unique()->values();

        $removed = [];
        foreach ($files as $file) {
            if (!$keep->contains($file)) {
                $disk->delete($file);
                $removed[] = $file;
            }
        }

        if ($removed) {
            Log::info('Backup retention cleanup', [
                'removed_count' => count($removed),
                'kept_count' => $keep->count(),
            ]);
        }

        return $removed;
    }

    /**
     * Restore a backup. Caller must double-check destructive intent at the
     * controller/command layer; this method just executes the steps.
     */
    public function restore(string $remotePath): array
    {
        $disk = Storage::disk(config('backup.disk', $this->disk));
        if (!$disk->exists($remotePath)) {
            throw new RuntimeException("Backup file not found on {$this->disk}: {$remotePath}");
        }

        $tmpDir = storage_path('app/tmp/backups');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $localPath = $tmpDir . DIRECTORY_SEPARATOR . basename($remotePath);

        // Stream-download to local tmp.
        $remoteStream = $disk->readStream($remotePath);
        $localStream = fopen($localPath, 'wb');
        if (!$remoteStream || !$localStream) {
            throw new RuntimeException("Cannot stream backup file locally");
        }
        stream_copy_to_stream($remoteStream, $localStream);
        if (is_resource($remoteStream)) fclose($remoteStream);
        if (is_resource($localStream)) fclose($localStream);

        $dumpPath = $localPath;

        // Decrypt if needed.
        if (Str::endsWith($localPath, '.gpg')) {
            $passphrase = config('backup.encryption_passphrase');
            if (empty($passphrase)) {
                throw new RuntimeException(
                    "Backup is encrypted but no BACKUP_ENCRYPTION_PASSPHRASE is configured"
                );
            }
            $decryptedPath = Str::beforeLast($localPath, '.gpg');
            $this->runGpgDecrypt($localPath, $decryptedPath, $passphrase);
            @unlink($localPath);
            $dumpPath = $decryptedPath;
        }

        $this->runPgRestore($dumpPath);
        @unlink($dumpPath);

        return [
            'restored_from' => $remotePath,
            'restored_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{path: string, filename: string, size_bytes: int, last_modified: string|null}>
     */
    public function list(): array
    {
        $disk = Storage::disk(config('backup.disk', $this->disk));
        $path = config('backup.path', $this->path);

        return collect($disk->files($path))
            ->filter(fn ($file) => Str::contains($file, 'pansin-'))
            ->sortDesc()
            ->values()
            ->map(fn ($file) => [
                'path' => $file,
                'filename' => basename($file),
                'size_bytes' => (int) $disk->size($file),
                'last_modified' => $disk->lastModified($file)
                    ? gmdate('c', (int) $disk->lastModified($file))
                    : null,
            ])
            ->all();
    }

    private function runPgDump(string $outputPath): void
    {
        $connection = config('database.connections.' . config('database.default'));
        if (!$connection || ($connection['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('Backup currently only supports the pgsql driver');
        }

        $bin = config('backup.pg_dump_binary', 'pg_dump');

        $command = [
            $bin,
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--username=' . $connection['username'],
            '--dbname=' . $connection['database'],
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--file=' . $outputPath,
        ];

        $process = new Process($command);
        $process->setTimeout((int) config('backup.timeout_seconds', 1800));
        $process->setEnv(['PGPASSWORD' => $connection['password'] ?? '']);
        try {
            $process->mustRun();
        } catch (ProcessTimedOutException $e) {
            throw new RuntimeException('pg_dump timed out: ' . $e->getMessage(), previous: $e);
        }
    }

    private function runPgRestore(string $inputPath): void
    {
        $connection = config('database.connections.' . config('database.default'));
        $bin = config('backup.pg_restore_binary', 'pg_restore');

        $command = [
            $bin,
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--username=' . $connection['username'],
            '--dbname=' . $connection['database'],
            '--clean',
            '--if-exists',
            '--no-owner',
            '--no-acl',
            $inputPath,
        ];

        $process = new Process($command);
        $process->setTimeout((int) config('backup.timeout_seconds', 1800));
        $process->setEnv(['PGPASSWORD' => $connection['password'] ?? '']);
        $process->mustRun();
    }

    private function runGpgEncrypt(string $input, string $output, string $passphrase): void
    {
        $bin = config('backup.gpg_binary', 'gpg');
        $command = [
            $bin,
            '--batch', '--yes',
            '--symmetric',
            '--cipher-algo', 'AES256',
            '--passphrase-fd', '0',
            '--output', $output,
            $input,
        ];

        $process = new Process($command);
        $process->setTimeout((int) config('backup.timeout_seconds', 1800));
        $process->setInput($passphrase);
        $process->mustRun();
    }

    private function runGpgDecrypt(string $input, string $output, string $passphrase): void
    {
        $bin = config('backup.gpg_binary', 'gpg');
        $command = [
            $bin,
            '--batch', '--yes',
            '--decrypt',
            '--passphrase-fd', '0',
            '--output', $output,
            $input,
        ];

        $process = new Process($command);
        $process->setTimeout((int) config('backup.timeout_seconds', 1800));
        $process->setInput($passphrase);
        $process->mustRun();
    }

    private function parseTimestampFromFilename(string $filename): ?\DateTimeImmutable
    {
        // pansin-YYYYmmdd_HHiiss.dump[.gpg]
        if (preg_match('/pansin-(\d{8})_(\d{6})\.dump/', $filename, $m)) {
            try {
                return new \DateTimeImmutable($m[1] . 'T' . $m[2]);
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }
}
