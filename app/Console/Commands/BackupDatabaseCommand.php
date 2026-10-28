<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Copia el archivo SQLite a storage/app/private/backups';

    public function handle(): int
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            $this->error('La copia está preparada para el archivo SQLite local.');

            return self::FAILURE;
        }

        $source = (string) config("database.connections.{$connection}.database");

        if ($source === '' || $source === ':memory:' || ! is_file($source)) {
            $this->error('No hay un archivo SQLite para copiar.');

            return self::FAILURE;
        }

        $directory = storage_path('app/private/backups');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error('No se pudo crear la carpeta de copias.');

            return self::FAILURE;
        }

        $target = $directory.'/database-'.now()->format('Ymd-His').'.sqlite';

        if (! copy($source, $target)) {
            $this->error('No se pudo copiar la base.');

            return self::FAILURE;
        }

        $this->info($target);

        return self::SUCCESS;
    }
}
