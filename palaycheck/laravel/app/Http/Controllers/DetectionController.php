<?php

namespace App\Http\Controllers;

use App\Models\Pest;

class DetectionController extends Controller
{
    public function index()
    {
        return view('detection', [
            'pests' => Pest::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
