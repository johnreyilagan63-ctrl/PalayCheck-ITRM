<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advice;
use App\Models\Detection;
use App\Services\DetectionService;
use App\Services\InferenceServiceManager;
use App\Services\PestMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class DetectionApiController extends Controller
{
    public function __invoke(
        Request $request,
        DetectionService $service,
        PestMatcher $matcher,
        InferenceServiceManager $inference
    ) {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:12288'],
        ], [
            'image.required' => 'Choose an image to scan.',
            'image.file' => 'The upload was interrupted. Try again.',
            'image.mimetypes' => 'Upload a valid JPEG, PNG, or WebP image.',
            'image.max' => 'The image must be no larger than 12 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'error' => $validator->errors()->first()], 400);
        }

        if (detection_provider() === 'local' && local_inference_configured()) {
            $inference->ensureRunning(8);
        }

        try {
            $file = $request->file('image');
            $threshold = detection_threshold();
            $save = (string) $request->input('save', '') === '1';

            $detections = $service->detect(
                $file->getRealPath(),
                (string) $file->getMimeType(),
                $threshold
            );

            foreach ($detections as &$detection) {
                if (! isset($detection['label'], $detection['confidence'], $detection['box'])
                    || ! is_array($detection['box'])
                    || count($detection['box']) !== 4) {
                    throw new RuntimeException('The AI model returned a malformed detection.');
                }

                $pest = $matcher->match((string) $detection['label']);
                $detection['pest_id'] = $pest?->id;
                $detection['pest_name'] = $pest?->name ?? $detection['label'];
            }
            unset($detection);

            usort($detections, static function (array $a, array $b): int {
                $matched = ($b['pest_id'] !== null) <=> ($a['pest_id'] !== null);

                return $matched !== 0 ? $matched : ((float) $b['confidence'] <=> (float) $a['confidence']);
            });

            $response = ['ok' => true, 'detections' => $detections, 'saved' => false];

            if ($detections) {
                $severity = in_array($request->input('severity'), ['low', 'medium', 'high'], true)
                    ? (string) $request->input('severity')
                    : $service->autoSeverity($detections);

                $response['severity'] = $severity;
                $top = $detections[0];

                if ($top['pest_id']) {
                    $response['advice'] = Advice::where('pest_id', $top['pest_id'])
                        ->where('status', 'published')
                        ->limit(3)
                        ->get(['title', 'content'])
                        ->map(static fn (Advice $item): array => [
                            'title' => $item->title,
                            'content' => $item->content,
                        ])
                        ->all();
                }

                if ($save) {
                    $mime = (string) $file->getMimeType();
                    $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';
                    $filename = bin2hex(random_bytes(16)).'.'.$extension;
                    $directory = rtrim((string) config('palaycheck.upload_dir'), '/\\');

                    if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
                        throw new RuntimeException('The image upload folder could not be created.');
                    }

                    $file->move($directory, $filename);

                    try {
                        $detection = Detection::create([
                            'user_id' => $request->user()->id,
                            'pest_id' => $top['pest_id'],
                            'label' => $top['pest_name'],
                            'confidence' => $top['confidence'],
                            'severity' => $severity,
                            'count' => count($detections),
                            'image_file' => $filename,
                            'source' => in_array($request->input('source'), ['camera', 'upload'], true)
                                ? (string) $request->input('source')
                                : 'upload',
                            'note' => mb_substr((string) $request->input('hint', ''), 0, 200),
                            'created_at' => now(),
                        ]);
                    } catch (Throwable $error) {
                        @unlink($directory.DIRECTORY_SEPARATOR.$filename);
                        throw $error;
                    }

                    log_activity('detection', $top['pest_name'].' '.round((float) $top['confidence'] * 100).'%');

                    $response['saved'] = true;
                    $response['id'] = $detection->id;
                    $response['url'] = route('history.show', $detection->id);
                }
            }

            return response()->json($response);
        } catch (RuntimeException $error) {
            return response()->json(['ok' => false, 'error' => $error->getMessage()], 503);
        } catch (Throwable $error) {
            report($error);

            return response()->json(['ok' => false, 'error' => 'The scan could not be completed. Please try again.'], 500);
        }
    }
}
