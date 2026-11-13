<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings', [
            'threshold' => (int) round((float) setting('threshold', '0.5') * 100),
        ]);
    }

    public function update(Request $request)
    {
        $threshold = filter_var($request->input('threshold'), FILTER_VALIDATE_FLOAT);

        if ($threshold === false || $threshold < 5 || $threshold > 99) {
            flash('Threshold must be between 5 and 99 percent.', 'danger');

            return back();
        }

        save_setting('threshold', (string) ($threshold / 100));
        flash('Settings saved.');

        return redirect()->route('admin.settings.index');
    }
}
