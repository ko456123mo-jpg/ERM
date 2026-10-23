<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class JadxService
{
    private string $binary;
    private int $timeout;
    private string $outputRoot;

    public function __construct()
    {
        $this->binary = (string) config('services.jadx.binary', '/usr/local/bin/jadx');
        $this->timeout = max(30, (int) config('services.jadx.timeout', 900));
        $this->outputRoot = storage_path('app/private/jadx');
    }

    public function checkHealth(): array
    {
        if (!is_file($this->binary) || !is_executable($this->binary)) {
            return [
                'available' => false,
                'binary' => $this->binary,
                'message' => 'JADX executable is missing or not executable.',
            ];
        }

        try {
            $process = new Process([$this->binary, '--version']);
            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'available' => false,
                    'binary' => $this->binary,
                    'message' => trim($process->getErrorOutput()) ?: 'JADX version check failed.',
                ];
            }

            return [
                'available' => true,
                'binary' => $this->binary,
                'version' => trim($process->getOutput()),
            ];
        } catch (Throwable $exception) {
            return [
                'available' => false,
                'binary' => $this->binary,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** Decompile the APK already stored for this Application record. */
    public function decompile(Application $application): array
    {
        $health = $this->checkHealth();
        if (!$health['available']) {
            throw new RuntimeException($health['message']);
        }

        $relativePath = $application->storage_path;
        if (!$relativePath || str_contains($relativePath, "\0")) {
            throw new RuntimeException('This application does not have a valid stored APK path.');
        }

        $apkPath = storage_path('app/' . ltrim($relativePath, '/'));
        if (!is_file($apkPath) || !is_readable($apkPath)) {
            throw new RuntimeException('The stored APK could not be found or is not readable.');
        }
        if (strtolower(pathinfo($apkPath, PATHINFO_EXTENSION)) !== 'apk') {
            throw new RuntimeException('The stored application file is not an APK.');
        }

        $runId = now()->format('Ymd_His') . '_' . Str::lower(Str::random(8));
        $outputDirectory = $this->outputRoot . DIRECTORY_SEPARATOR
            . $application->id . DIRECTORY_SEPARATOR . $runId;

        File::ensureDirectoryExists(dirname($outputDirectory), 0750, true);
        if (!File::makeDirectory($outputDirectory, 0750, true, true)) {
            throw new RuntimeException('Could not create the JADX output directory.');
        }

        // Use argv array rather than a shell string to avoid shell injection.
        $process = new Process([
            $this->binary,
            '--no-res',
            '--output-dir',
            $outputDirectory,
            $apkPath,
        ]);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'JADX could not complete: ' . $exception->getMessage(),
                previous: $exception
            );
        }

        if (!$process->isSuccessful()) {
            $diagnostic = trim($process->getErrorOutput()) ?: trim($process->getOutput());
            throw new RuntimeException(
                'JADX decompilation failed: ' . mb_substr($diagnostic ?: 'JADX exited unsuccessfully.', 0, 3000)
            );
        }

        $files = [];
        $fileCount = 0;
        foreach (File::allFiles($outputDirectory) as $file) {
            $fileCount++;
            if (count($files) < 500) {
                $files[] = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
            }
        }

        return [
            'status' => 'completed',
            'application_id' => $application->id,
            'run_id' => $runId,
            'file_count' => $fileCount,
            'files' => $files,
            'files_truncated' => $fileCount > count($files),
            'output_path' => $outputDirectory,
            'jadx_version' => $health['version'] ?? null,
        ];
    }
}
