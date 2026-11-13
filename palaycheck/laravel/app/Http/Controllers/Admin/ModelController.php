<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index()
    {
        return view('admin.model', [
            'status' => model_status(),
            'inferenceUrl' => (string) config('palaycheck.inference.url'),
            'inferenceToken' => trim((string) config('palaycheck.inference.token')) !== '',
            'classes' => array_values(array_filter((array) config('palaycheck.inference.classes'), 'is_string')),
            'roboflowKey' => trim((string) config('palaycheck.roboflow.api_key')) !== '',
            'workflows' => array_values(array_filter((array) config('palaycheck.roboflow.workflows'), 'is_string')),
        ]);
    }

    public function store(Request $request)
    {
        flash('Detection settings are configured in .env and config/palaycheck.php.', 'info');

        return redirect()->route('admin.model.index');
    }
}
