<?php

use App\Models\ActivityLog;
use App\Models\Detection;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

if (! function_exists('flash')) {
    function flash(string $message, string $category = 'success'): void
    {
        $flashes = session()->get('flashes', []);
        $flashes[] = ['category' => $category, 'message' => $message];
        session()->flash('flashes', $flashes);
    }
}

if (! function_exists('log_activity')) {
    function log_activity(string $action, string $details = ''): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'details' => mb_substr($details, 0, 250),
            'created_at' => now(),
        ]);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        $value = Setting::find($key)?->value;

        return $value === null ? $default : (string) $value;
    }
}

if (! function_exists('save_setting')) {
    function save_setting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

if (! function_exists('pest_image')) {
    function pest_image(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
        if ($slug === '') {
            return null;
        }

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $relative = 'static/img/pests/'.$slug.'.'.$extension;
            if (is_file(public_path($relative))) {
                return '/'.$relative;
            }
        }

        return null;
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime($value, string $format = 'M d, Y H:i'): string
    {
        return $value ? Carbon::parse($value)->format($format) : '';
    }
}

if (! function_exists('local_inference_configured')) {
    function local_inference_configured(): bool
    {
        return trim((string) config('palaycheck.inference.url')) !== '';
    }
}

if (! function_exists('detection_provider')) {
    function detection_provider(): string
    {
        return strtolower(trim((string) config('palaycheck.provider', 'local'))) === 'roboflow'
            ? 'roboflow'
            : 'local';
    }
}

if (! function_exists('roboflow_configured')) {
    function roboflow_configured(): bool
    {
        return trim((string) config('palaycheck.roboflow.api_key')) !== ''
            && count((array) config('palaycheck.roboflow.workflows')) > 0;
    }
}

if (! function_exists('model_configured')) {
    function model_configured(): bool
    {
        return local_inference_configured() || roboflow_configured();
    }
}

if (! function_exists('model_status')) {
    function model_status(): array
    {
        $file = setting('model_file', 'palaycheck_best.pt');
        $directory = rtrim((string) config('palaycheck.model_dir'), '/\\');
        $threshold = (float) setting('threshold', '0.5');

        return [
            'ready' => model_configured(),
            'exists' => is_file($directory.DIRECTORY_SEPARATOR.$file),
            'file' => $file,
            'threshold' => $threshold,
            'error' => null,
        ];
    }
}

if (! function_exists('detection_threshold')) {
    function detection_threshold(): float
    {
        return min(max((float) setting('threshold', '0.5'), 0.05), 0.99);
    }
}

if (! function_exists('remove_detection')) {
    function remove_detection(Detection $detection): void
    {
        if ($detection->image_file) {
            $path = rtrim((string) config('palaycheck.upload_dir'), '/\\')
                .DIRECTORY_SEPARATOR.basename($detection->image_file);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $detection->delete();
    }
}
