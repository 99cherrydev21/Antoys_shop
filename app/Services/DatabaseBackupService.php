<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    /**
     * Genera un respaldo completo de la base de datos.
     */
    public function create(): array
    {
        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $dumpPath = config(
            'services.maintenance.mysql_dump_path',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe'
        );

        if (!$database || !$host || !$username) {
            throw new RuntimeException(
                'La configuración de conexión MySQL está incompleta.'
            );
        }

        if (!File::exists($dumpPath)) {
            throw new RuntimeException(
                'No se encontró mysqldump.exe en: '.$dumpPath
            );
        }

        // Carpeta privada: nunca dentro de public.
        $directory = storage_path('app/private/maintenance-backups');

        File::ensureDirectoryExists($directory);

        $filename = 'antoys-backup-'
            .now()->format('Y-m-d_H-i-s')
            .'-'.str()->random(8)
            .'.sql';

        $fullPath = $directory.DIRECTORY_SEPARATOR.$filename;

        // Si Laravel utiliza localhost, preferimos IPv4 local.
        $host = $host === 'localhost' ? '127.0.0.1' : $host;

        $command = [
            $dumpPath,
            '--protocol=TCP',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
            '--column-statistics=0',
            '--no-tablespaces',
            '--result-file='.$fullPath,
            '--databases',
            $database,
        ];

        try {
            /*
             * Heredamos las variables de Windows.
             * Esto evita iniciar mysqldump con un entorno incompleto.
             */
            $environment = getenv();

            if (!is_array($environment)) {
                $environment = [];
            }

            // Aseguramos variables importantes en Windows.
            foreach (['SystemRoot', 'WINDIR', 'PATH', 'TEMP', 'TMP'] as $key) {
                $value = getenv($key);

                if ($value !== false && $value !== '') {
                    $environment[$key] = $value;
                }
            }

            // La contraseña no se agrega a los argumentos del comando.
            $environment['MYSQL_PWD'] = (string) $password;

            $process = new Process(
                $command,
                base_path(),
                $environment
            );

            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                $error = trim($process->getErrorOutput());
                $output = trim($process->getOutput());

                throw new RuntimeException(
                    'Error al generar el respaldo: '
                    .($error ?: $output ?: 'mysqldump terminó sin proporcionar detalles.')
                );
            }

            if (!File::exists($fullPath) || File::size($fullPath) <= 0) {
                throw new RuntimeException(
                    'MySQL no generó un archivo de respaldo válido.'
                );
            }

            $checksum = hash_file('sha256', $fullPath);

            if ($checksum === false) {
                throw new RuntimeException(
                    'No fue posible calcular el SHA-256 del respaldo.'
                );
            }

            return [
                'filename' => $filename,
                'path' => $fullPath,
                'checksum' => $checksum,
                'size' => File::size($fullPath),
                'created_at' => now(),
            ];

        } catch (Throwable $exception) {
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            throw $exception;
        }
    }
}
