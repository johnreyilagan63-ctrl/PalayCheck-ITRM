<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DetectionService
{
    public function detect(string $filePath, string $mime, float $threshold): array
    {
        if (detection_provider() === 'roboflow' && roboflow_configured()) {
            return $this->roboflowDetect($filePath, $threshold);
        }

        if (! local_inference_configured()) {
            return $this->roboflowDetect($filePath, $threshold);
        }

        try {
            return $this->localDetect($filePath, $mime, $threshold);
        } catch (RuntimeException $localError) {
            if (! roboflow_configured()) {
                throw $localError;
            }

            try {
                return $this->roboflowDetect($filePath, $threshold);
            } catch (RuntimeException $remoteError) {
                throw new RuntimeException($this->combineDetectionErrors($localError, $remoteError));
            }
        }
    }

    public function roboflowOnline(): bool
    {
        $apiKey = trim((string) config('palaycheck.roboflow.api_key'));
        $url = rtrim(trim((string) config('palaycheck.roboflow.api_url', 'https://api.roboflow.com')), '/');

        if ($apiKey === '' || $url === '') {
            return false;
        }

        try {
            $request = Http::acceptJson()
                ->connectTimeout((int) config('palaycheck.roboflow.connect_timeout', 10))
                ->timeout((int) config('palaycheck.roboflow.timeout', 45));

            $caBundle = (string) config('palaycheck.roboflow.ca_bundle');
            if ($caBundle !== '' && is_file($caBundle)) {
                $request = $request->withOptions(['verify' => $caBundle]);
            }

            return $request->get($url, ['api_key' => $apiKey])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function combineDetectionErrors(RuntimeException $localError, RuntimeException $remoteError): string
    {
        $local = trim($localError->getMessage());
        $remote = trim($remoteError->getMessage());

        if ($local !== '' && $remote !== ''
            && str_contains(strtolower($local), 'local detection service is not running')
            && str_contains(strtolower($remote), 'no inference credits left')) {
            return $local.' '.$remote;
        }

        $messages = array_values(array_filter([$local, $remote], static fn (string $message): bool => $message !== ''));

        return $messages === [] ? 'Detection failed.' : implode(' Fallback: ', $messages);
    }

    public function autoSeverity(array $detections): string
    {
        $area = 0.0;
        foreach ($detections as $detection) {
            $box = $detection['box'];
            $area += ($box[2] - $box[0]) * ($box[3] - $box[1]);
        }

        if (count($detections) >= 5 || $area > 0.25) {
            return 'high';
        }

        if (count($detections) >= 2 || $area > 0.08) {
            return 'medium';
        }

        return 'low';
    }

    private function localDetect(string $filePath, string $mime, float $threshold): array
    {
        $url = rtrim(trim((string) config('palaycheck.inference.url')), '/');
        if ($url === '') {
            throw new RuntimeException('Local detection is not configured.');
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        $classes = array_values(array_filter((array) config('palaycheck.inference.classes'), 'is_string'));
        $data = ['threshold' => (string) $threshold];
        if ($classes) {
            $data['classes'] = implode(',', $classes);
        }

        $headers = ['Accept' => 'application/json'];
        $token = trim((string) config('palaycheck.inference.token'));
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        try {
            $response = Http::connectTimeout((int) config('palaycheck.inference.connect_timeout', 2))
                ->timeout((int) config('palaycheck.inference.timeout', 60))
                ->withHeaders($headers)
                ->attach('image', $contents, basename($filePath), ['Content-Type' => $mime])
                ->post($url.'/detect', $data);
        } catch (ConnectionException) {
            throw new RuntimeException(
                'The local detection service is not running. Start it with start_inference.bat and try again.'
            );
        }

        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('error') ?? 'Local detection service failed with HTTP '.$response->status().'.'));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('The local detection service returned an invalid response.');
        }

        $detections = [];
        foreach ((array) ($payload['detections'] ?? []) as $detection) {
            if (! is_array($detection)) {
                continue;
            }

            $confidence = (float) ($detection['confidence'] ?? 0);
            $label = trim((string) ($detection['label'] ?? ''));
            $box = $detection['box'] ?? null;

            if ($confidence < $threshold || $label === '' || ! is_array($box) || count($box) !== 4) {
                continue;
            }

            $box = array_map(
                static fn ($value): float => max(0.0, min(1.0, (float) $value)),
                array_values($box)
            );

            if ($box[2] <= $box[0] || $box[3] <= $box[1]) {
                continue;
            }

            $detections[] = ['label' => $label, 'confidence' => $confidence, 'box' => $box];
        }

        return $this->dedupe($detections);
    }

    private function roboflowDetect(string $filePath, float $threshold): array
    {
        $apiKey = trim((string) config('palaycheck.roboflow.api_key'));
        $workflows = array_values(array_filter((array) config('palaycheck.roboflow.workflows'), 'is_string'));

        if ($apiKey === '' || ! $workflows) {
            throw new RuntimeException('AI scanning is not configured. Add the Roboflow API key and workflow URLs to the Laravel configuration.');
        }

        $image = file_get_contents($filePath);
        if ($image === false) {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        $payload = [
            'inputs' => ['image' => ['type' => 'base64', 'value' => base64_encode($image)]],
        ];
        $urls = [$workflows[0]];
        $caBundle = (string) config('palaycheck.roboflow.ca_bundle');

        try {
            $responses = Http::pool(function (Pool $pool) use ($urls, $apiKey, $payload, $caBundle): void {
                foreach ($urls as $index => $url) {
                    $request = $pool->as((string) $index)
                        ->withToken($apiKey)
                        ->acceptJson()
                        ->connectTimeout((int) config('palaycheck.roboflow.connect_timeout', 10))
                        ->timeout((int) config('palaycheck.roboflow.timeout', 45));

                    if ($caBundle !== '' && is_file($caBundle)) {
                        $request->withOptions(['verify' => $caBundle]);
                    }

                    $request->post($url, $payload);
                }
            });
        } catch (Throwable $error) {
            throw new RuntimeException('Roboflow could not scan the image: '.$error->getMessage());
        }

        $detections = [];
        $errors = [];
        $successful = 0;

        foreach ($urls as $index => $url) {
            $response = $responses[(string) $index] ?? null;

            if (! $response instanceof Response) {
                $errors[] = 'No response from Roboflow.';

                continue;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                $errors[] = 'Roboflow returned an invalid response.';

                continue;
            }

            if ($response->failed()) {
                $message = (string) ($payload['error'] ?? $payload['message'] ?? 'Roboflow request failed with HTTP '.$response->status().'.');

                if (($payload['reason'] ?? '') === 'credit_cap_exceeded' || str_contains(strtolower($message), 'credit')) {
                    $message = 'Roboflow has no inference credits left for this workspace. Raise the credit cap or upgrade the plan at https://app.roboflow.com/settings/plan.';
                }

                $errors[] = $message;

                continue;
            }

            $successful++;
            $detections = array_merge($detections, $this->roboflowDetections($payload, $threshold));
        }

        if ($successful === 0) {
            throw new RuntimeException($errors[0] ?? 'Roboflow could not scan the image.');
        }

        return $this->dedupe($detections);
    }

    private function roboflowDetections(array $response, float $threshold): array
    {
        $outputs = $response['outputs'] ?? null;
        if (! is_array($outputs)) {
            return [];
        }

        $detections = [];

        foreach ($outputs as $output) {
            if (! is_array($output)) {
                continue;
            }

            $predictionSet = $output['predictions'] ?? null;
            if (! is_array($predictionSet)) {
                continue;
            }

            $predictions = $predictionSet['predictions'] ?? $predictionSet;
            if (! is_array($predictions)) {
                continue;
            }

            $image = $predictionSet['image'] ?? $output['image'] ?? [];
            $imageWidth = (float) ($image['width'] ?? 0);
            $imageHeight = (float) ($image['height'] ?? 0);

            foreach ($predictions as $prediction) {
                if (! is_array($prediction)) {
                    continue;
                }

                $confidence = (float) ($prediction['confidence'] ?? 0);
                $label = trim((string) ($prediction['class'] ?? $prediction['class_name'] ?? $prediction['label'] ?? ''));
                $x = (float) ($prediction['x'] ?? 0);
                $y = (float) ($prediction['y'] ?? 0);
                $width = (float) ($prediction['width'] ?? 0);
                $height = (float) ($prediction['height'] ?? 0);

                if ($confidence < $threshold || $label === '' || $width <= 0 || $height <= 0) {
                    continue;
                }

                if ($imageWidth <= 1 || $imageHeight <= 1) {
                    $imageWidth = max($imageWidth, 1);
                    $imageHeight = max($imageHeight, 1);
                }

                if ($x > 1 || $y > 1 || $width > 1 || $height > 1) {
                    $x /= $imageWidth;
                    $y /= $imageHeight;
                    $width /= $imageWidth;
                    $height /= $imageHeight;
                }

                $box = [
                    max(0.0, min(1.0, $x - $width / 2)),
                    max(0.0, min(1.0, $y - $height / 2)),
                    max(0.0, min(1.0, $x + $width / 2)),
                    max(0.0, min(1.0, $y + $height / 2)),
                ];

                if ($box[2] <= $box[0] || $box[3] <= $box[1]) {
                    continue;
                }

                $detections[] = ['label' => $label, 'confidence' => $confidence, 'box' => $box];
            }
        }

        return $detections;
    }

    private function dedupe(array $detections): array
    {
        usort($detections, static fn (array $a, array $b): int => (float) $b['confidence'] <=> (float) $a['confidence']);

        $kept = [];
        foreach ($detections as $detection) {
            foreach ($kept as $existing) {
                if ($this->iou($detection['box'], $existing['box']) >= 0.55) {
                    continue 2;
                }
            }

            $kept[] = $detection;
            if (count($kept) >= 50) {
                break;
            }
        }

        return $kept;
    }

    private function iou(array $a, array $b): float
    {
        $x1 = max($a[0], $b[0]);
        $y1 = max($a[1], $b[1]);
        $x2 = min($a[2], $b[2]);
        $y2 = min($a[3], $b[3]);
        $intersection = max(0.0, $x2 - $x1) * max(0.0, $y2 - $y1);

        if ($intersection <= 0) {
            return 0.0;
        }

        $areaA = max(0.0, $a[2] - $a[0]) * max(0.0, $a[3] - $a[1]);
        $areaB = max(0.0, $b[2] - $b[0]) * max(0.0, $b[3] - $b[1]);
        $union = $areaA + $areaB - $intersection;

        return $union > 0 ? $intersection / $union : 0.0;
    }
}
