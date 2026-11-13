<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class ActivityController extends Controller
{
    public function index()
    {
        return view('admin.activity', [
            'rows' => ActivityLog::with('user')->orderByDesc('created_at')->limit(300)->get(),
        ]);
    }
}
