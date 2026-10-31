<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Portable database dump used by the scheduler (SRS 5.4).
 *
 * Production Linux still uses scripts/backup.sh for a full archive (database
 * + uploads + .env). This command is the always-available dump that runs from
 * `php artisan schedule:run` even when a shell wrapper is not installed.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--keep=14 : Days of dumps to keep}';

    protected $description = 'Dump the application database into storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0700);

        $stamp = now()->format('Ymd_His');
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}", []);
        $driver = $config['driver'] ?? $connection;

        if ($driver === 'sqlite') {
            $database = (string) ($config['database'] ?? '');

            if ($database === ':memory:' || $database === '' || ! is_file($database)) {
                $this->warn('SQLite in-memory databases cannot be dumped; skipping.');

                return self::SUCCESS;
            }

            $target = $dir.DIRECTORY_SEPARATOR."database_{$stamp}.sqlite";
            File::copy($database, $target);
            $this->info("SQLite dump written: {$target}");
        } else {
            $mysqldump = $this->mysqldumpBinary();

            if ($mysqldump === null) {
                $this->error('mysqldump is not on PATH. Install mysql-client or use scripts/backup.sh.');

                return self::FAILURE;
            }

            $target = $dir.DIRECTORY_SEPARATOR."database_{$stamp}.sql.gz";
            $passwordFile = $dir.DIRECTORY_SEPARATOR.'.my.cnf.tmp';

            File::put($passwordFile, sprintf(
                "[client]\nhost=%s\nport=%s\nuser=%s\npassword=\"%s\"\n",
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 3306,
                $config['username'] ?? 'root',
                str_replace(['\\', '"'], ['\\\\', '\\"'], (string) ($config['password'] ?? ''))
            ));
            chmod($passwordFile, 0600);

            try {
                $process = Process::fromShellCommandline(
                    sprintf(
                        '%s --defaults-extra-file=%s --single-transaction --quick --routines --events --no-tablespaces %s | gzip > %s',
                        escapeshellarg($mysqldump),
                        escapeshellarg($passwordFile),
                        escapeshellarg((string) ($config['database'] ?? '')),
                        escapeshellarg($target)
                    )
                );
                $process->setTimeout(300);
                $process->run();

                if (! $process->isSuccessful() || ! is_file($target) || filesize($target) === 0) {
                    $this->error(trim($process->getErrorOutput()) ?: 'mysqldump produced an empty file.');

                    return self::FAILURE;
                }
            } finally {
                File::delete($passwordFile);
            }

            $this->info("MySQL dump written: {$target}");
        }

        $this->prune($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function mysqldumpBinary(): ?string
    {
        $candidates = ['mysqldump', 'mysqldump.exe'];

        foreach ($candidates as $binary) {
            $process = Process::fromShellCommandline(
                PHP_OS_FAMILY === 'Windows' ? 'where '.$binary : 'command -v '.$binary
            );
            $process->run();

            if ($process->isSuccessful()) {
                return trim(strtok($process->getOutput(), "\n"));
            }
        }

        return null;
    }

    private function prune(string $dir, int $keepDays): void
    {
        $cutoff = now()->subDays(max(1, $keepDays))->getTimestamp();

        foreach (File::files($dir) as $file) {
            if (! preg_match('/^database_\d{8}_\d{6}\.(sql\.gz|sqlite)$/', $file->getFilename())) {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }
}
