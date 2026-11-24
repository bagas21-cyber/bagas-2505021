<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=7 : Number of recent backups to keep}';

    protected $description = 'Salin database SQLite ke penyimpanan backup aplikasi';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('Perintah ini hanya mendukung koneksi database SQLite.');

            return self::FAILURE;
        }

        $source = (string) config('database.connections.sqlite.database');
        $destinationDirectory = storage_path('app/backups');

        if (! is_file($source)) {
            $this->error("Database SQLite tidak ditemukan: {$source}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists($destinationDirectory);
        DB::connection('sqlite')->getPdo()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        $destination = $destinationDirectory.DIRECTORY_SEPARATOR.'database-'.now()->format('Ymd-His').'.sqlite';

        if (! File::copy($source, $destination)) {
            throw new RuntimeException('Backup database gagal disalin.');
        }

        $keep = max(1, (int) $this->option('keep'));
        $backups = collect(File::files($destinationDirectory))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.sqlite'))
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->values();

        $backups->slice($keep)->each(fn ($file) => File::delete($file->getPathname()));

        $this->info('Backup database berhasil: '.basename($destination));
        $this->line("Backup tersimpan di {$destinationDirectory} (maksimal {$keep} file). ");

        return self::SUCCESS;
    }
}
