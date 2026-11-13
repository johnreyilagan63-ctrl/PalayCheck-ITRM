<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DetectionService;
use App\Services\InferenceServiceManager;
use Illuminate\Support\Facades\Cache;

class DetectionStatusController extends Controller
{
    public function __invoke(InferenceServiceManager $inference, DetectionService $detection)
    {
        $provider = detection_provider();
        $localConfigured = $inference->configured();
        $roboflowConfigured = roboflow_configured();
        $localOnline = false;
        $autoStarted = false;

        if ($provider === 'local' && $localConfigured) {
            $localOnline = $inference->online();

            if (! $localOnline) {
                $autoStarted = $inference->ensureRunning(10);
                $localOnline = $autoStarted;
            }
        }

        $roboflowOnline = false;
        if ($provider === 'roboflow' && $roboflowConfigured) {
            $roboflowOnline = Cache::remember(
                'palaycheck.roboflow.online',
                300,
                static fn (): bool => $detection->roboflowOnline()
            );
        }

        return response()->json([
            'ready' => $provider === 'roboflow'
                ? $roboflowOnline
                : ($localOnline || $roboflowConfigured),
            'primary_provider' => $provider,
            'local_configured' => $localConfigured,
            'local_online' => $localOnline,
            'auto_started' => $autoStarted,
            'roboflow_configured' => $roboflowConfigured,
            'roboflow_online' => $roboflowOnline,
        ]);
    }
}
