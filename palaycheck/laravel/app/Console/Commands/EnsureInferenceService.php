<?php

namespace App\Console\Commands;

use App\Services\InferenceServiceManager;
use Illuminate\Console\Command;

class EnsureInferenceService extends Command
{
    protected $signature = 'palaycheck:inference {--wait=15 : Seconds to wait for the service to become ready}';

    protected $description = 'Start the local rice-pest detection service if it is not already running';

    public function handle(InferenceServiceManager $inference): int
    {
        if (! $inference->configured()) {
            $this->error('INFERENCE_URL is not configured.');

            return self::FAILURE;
        }

        if ($inference->online()) {
            $this->info('Local detection service is already running.');

            return self::SUCCESS;
        }

        $this->line('Starting the local detection service...');

        if (! $inference->ensureRunning((int) $this->option('wait'))) {
            $this->error('The local detection service did not start. Run start_inference.bat to see the error.');

            return self::FAILURE;
        }

        $this->info('Local detection service is ready.');

        return self::SUCCESS;
    }
}
