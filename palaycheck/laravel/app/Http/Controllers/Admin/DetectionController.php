<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Detection;
use Illuminate\Http\Request;

class DetectionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $severity = (string) $request->query('severity', '');

        if (! in_array($severity, ['low', 'medium', 'high'], true)) {
            $severity = '';
        }

        $rows = Detection::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('label', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($query) => $query->where('username', 'like', '%'.$search.'%'));
                });
            })
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.detections', [
            'rows' => $rows,
            'search' => $search,
            'severityFilter' => $severity,
        ]);
    }

    public function destroy(Detection $detection)
    {
        remove_detection($detection);
        log_activity('admin_delete', 'detections #'.$detection->id);
        flash('Detection deleted.');

        $previous = url()->previous();
        if ($previous !== '' && str_starts_with($previous, url('/'))) {
            return redirect($previous);
        }

        return redirect()->route('admin.detections.index');
    }
}
