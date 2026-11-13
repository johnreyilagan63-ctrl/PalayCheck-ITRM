<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class InferenceServiceManager
{
    public function configured(): bool
    {
        return $this->baseUrl() !== '';
    }

    public function online(): bool
    {
        $url = $this->baseUrl();
        if ($url === '') {
            return false;
        }

        try {
            $request = Http::connectTimeout(1)->timeout(2)->acceptJson();
            $token = trim((string) config('palaycheck.inference.token'));
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->get($url.'/health');

            return $response->successful() && (bool) $response->json('ready', false);
        } catch (Throwable) {
            return false;
        }
    }

    public function ensureRunning(int $waitSeconds = 10): bool
    {
        if (! $this->configured() || ! config('palaycheck.inference.autostart', true) || app()->runningUnitTests()) {
            return false;
        }

        if ($this->online()) {
            return true;
        }

        if (Cache::add('palaycheck.inference.starting', true, 30)) {
            $this->start();
        }

        return $this->waitUntilOnline($waitSeconds);
    }

    private function waitUntilOnline(int $seconds): bool
    {
        $deadline = microtime(true) + max(1, $seconds);

        do {
            usleep(400000);
            if ($this->online()) {
                return true;
            }
        } while (microtime(true) < $deadline);

        return false;
    }

    private function start(): void
    {
        $script = dirname(base_path()).DIRECTORY_SEPARATOR.'inference_service.py';
        if (! is_file($script)) {
            return;
        }

        $python = $this->pythonBinary();
        if ($python === null) {
            return;
        }

        $command = PHP_OS_FAMILY === 'Windows'
            ? 'start "" /B "'.$python.'" "'.$script.'" > "'.$this->logFile().'" 2>&1'
            : 'nohup "'.$python.'" "'.$script.'" > "'.$this->logFile().'" 2>&1 &';

        try {
            $handle = @popen($command, 'r');
            if (is_resource($handle)) {
                pclose($handle);
            }
        } catch (Throwable) {
            // The health check that follows reports whether the service came up.
        }
    }

    private function pythonBinary(): ?string
    {
        $root = dirname(base_path());

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [
                $root.'\\venv\\Scripts\\python.exe',
                $root.'\\.venv\\Scripts\\python.exe',
            ]
            : [
                $root.'/venv/bin/python',
                $root.'/.venv/bin/python',
            ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }

    private function baseUrl(): string
    {
        return rtrim(trim((string) config('palaycheck.inference.url')), '/');
    }

    private function logFile(): string
    {
        $directory = storage_path('logs');
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        return $directory.DIRECTORY_SEPARATOR.'inference.log';
    }
}
